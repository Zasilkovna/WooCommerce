<?php

declare( strict_types=1 );

namespace Packetery\Module\Product;

use Packetery\Latte\Engine;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Log\ArgumentTypeErrorLogger;
use Packetery\Module\WpdbAdapter;
use Packetery\Nette\Http\Request;

class ProductGridExtender {

	private const TEMPLATE_GRID_COLUMN_AGE_VERIFICATION      = PACKETERY_PLUGIN_DIR . '/template/product/grid-column-age-verification.latte';
	private const TEMPLATE_GRID_COLUMN_SHIPPING_RESTRICTIONS = PACKETERY_PLUGIN_DIR . '/template/product/grid-column-shipping-restrictions.latte';
	private const TEMPLATE_FILTER_AGE_VERIFICATION           = PACKETERY_PLUGIN_DIR . '/template/product/filter-age-verification.latte';
	private const TEMPLATE_FILTER_SHIPPING_RESTRICTIONS      = PACKETERY_PLUGIN_DIR . '/template/product/filter-shipping-restrictions.latte';

	public const COLUMN_AGE_VERIFICATION      = 'packetery_age_verification';
	public const COLUMN_SHIPPING_RESTRICTIONS = 'packetery_shipping_restrictions';

	public const FILTER_AGE_VERIFICATION      = 'packetery_age_verification';
	public const FILTER_SHIPPING_RESTRICTIONS = 'packetery_shipping_restrictions';

	/** @var array<int, Entity> */
	private static array $productCache = [];
	private WpAdapter $wpAdapter;
	private Request $httpRequest;
	private ProductEntityFactory $productEntityFactory;
	private ArgumentTypeErrorLogger $argumentTypeErrorLogger;
	private WpdbAdapter $wpdbAdapter;
	private Engine $latteEngine;

	public function __construct(
		WpAdapter $wpAdapter,
		Request $httpRequest,
		ProductEntityFactory $productEntityFactory,
		ArgumentTypeErrorLogger $argumentTypeErrorLogger,
		WpdbAdapter $wpdbAdapter,
		Engine $latteEngine
	) {
		$this->wpAdapter               = $wpAdapter;
		$this->httpRequest             = $httpRequest;
		$this->productEntityFactory    = $productEntityFactory;
		$this->argumentTypeErrorLogger = $argumentTypeErrorLogger;
		$this->wpdbAdapter             = $wpdbAdapter;
		$this->latteEngine             = $latteEngine;
	}

	/**
	 * @param array<string, string>|mixed $columns
	 *
	 * @return array<string, string>|mixed
	 */
	public function addProductListColumns( $columns ) {
		if ( ! is_array( $columns ) ) {
			$this->argumentTypeErrorLogger->log( __METHOD__, 'columns', 'array', $columns );

			return $columns;
		}

		$columns[ self::COLUMN_AGE_VERIFICATION ]      = $this->wpAdapter->__( 'Age verification', 'packeta' );
		$columns[ self::COLUMN_SHIPPING_RESTRICTIONS ] = $this->wpAdapter->__( 'Shipping method restrictions', 'packeta' );

		return $columns;
	}

	public function fillCustomProductListColumns( string $column, int $postId ): void {
		if ( $column === self::COLUMN_AGE_VERIFICATION ) {
			$product = $this->getProductForPostId( $postId );
			$this->latteEngine->render(
				self::TEMPLATE_GRID_COLUMN_AGE_VERIFICATION,
				[
					'productId'          => $postId,
					'hasAgeVerification' => $product->isAgeVerificationRequired(),
					'translations'       => [
						'yes' => $this->wpAdapter->__( 'Yes', 'packeta' ),
					],
				]
			);
		}

		if ( $column === self::COLUMN_SHIPPING_RESTRICTIONS ) {
			$product                 = $this->getProductForPostId( $postId );
			$disallowedShippingRates = $product->getDisallowedShippingRateIds();
			$this->latteEngine->render(
				self::TEMPLATE_GRID_COLUMN_SHIPPING_RESTRICTIONS,
				[
					'productId'               => $postId,
					'hasShippingRestrictions' => count( $disallowedShippingRates ) > 0,
					'translations'            => [
						'yes' => $this->wpAdapter->__( 'Yes', 'packeta' ),
					],
				]
			);
		}

		unset( self::$productCache[ $postId ] );
	}

	/**
	 * @param array<int, string> $hidden
	 *
	 * @return array<int, string>
	 */
	public function defaultHiddenColumns( array $hidden, \WP_Screen $screen ): array {
		if ( $screen->id === 'edit-product' ) {
			$hidden[] = self::COLUMN_AGE_VERIFICATION;
			$hidden[] = self::COLUMN_SHIPPING_RESTRICTIONS;
		}

		return $hidden;
	}

	/**
	 * @param array<string, callable> $filters Product filters.
	 *
	 * @return array<string, callable> Product filters.
	 */
	public function addProductFilters( array $filters ): array {
		$enabledFilters = $this->getEnabledFilters();

		if ( $enabledFilters[ self::FILTER_AGE_VERIFICATION ] ?? false ) {
			$filters[ self::FILTER_AGE_VERIFICATION ] = [ $this, 'renderAgeVerificationFilter' ];
		}

		if ( $enabledFilters[ self::FILTER_SHIPPING_RESTRICTIONS ] ?? false ) {
			$filters[ self::FILTER_SHIPPING_RESTRICTIONS ] = [ $this, 'renderShippingRestrictionsFilter' ];
		}

		return $filters;
	}

	public function renderAgeVerificationFilter(): void {
		$this->latteEngine->render(
			self::TEMPLATE_FILTER_AGE_VERIFICATION,
			[
				'currentValue' => $this->httpRequest->getQuery( self::FILTER_AGE_VERIFICATION ),
				'translations' => [
					'filterByAgeVerification' => $this->wpAdapter->__( 'Filter by age verification', 'packeta' ),
					'yes'                     => $this->wpAdapter->__( 'Yes', 'packeta' ),
				],
			]
		);
	}

	public function renderShippingRestrictionsFilter(): void {
		$this->latteEngine->render(
			self::TEMPLATE_FILTER_SHIPPING_RESTRICTIONS,
			[
				'currentValue' => $this->httpRequest->getQuery( self::FILTER_SHIPPING_RESTRICTIONS ),
				'translations' => [
					'filterByShippingRestrictions' => $this->wpAdapter->__( 'Filter by shipping method restrictions', 'packeta' ),
					'yes'                          => $this->wpAdapter->__( 'Yes', 'packeta' ),
				],
			]
		);
	}

	/**
	 * @param array<string, string> $clauses
	 *
	 * @return array<string, string>
	 */
	public function processProductFilterClauses( array $clauses, \WP_Query $query ): array {
		if ( ! isset( $query->query['post_type'] ) || $query->query['post_type'] !== 'product' ) {
			return $clauses;
		}

		$ageVerificationFilter = $this->httpRequest->getQuery( self::FILTER_AGE_VERIFICATION );
		if ( $ageVerificationFilter === '1' ) {
			$metaKey           = Entity::META_AGE_VERIFICATION_18_PLUS;
			$clauses['join']  .= $this->wpdbAdapter->prepare( " LEFT JOIN `{$this->wpdbAdapter->postmeta}` AS `packetery_pm_age` ON `{$this->wpdbAdapter->posts}`.`ID` = `packetery_pm_age`.`post_id` AND `packetery_pm_age`.`meta_key` = %s", $metaKey );
			$clauses['where'] .= $this->wpdbAdapter->prepare( ' AND `packetery_pm_age`.`meta_value` = %s', '1' );
		}

		$shippingRestrictionsFilter = $this->httpRequest->getQuery( self::FILTER_SHIPPING_RESTRICTIONS );
		if ( $shippingRestrictionsFilter === '1' ) {
			$metaKey           = Entity::META_DISALLOWED_SHIPPING_RATES;
			$clauses['join']  .= $this->wpdbAdapter->prepare( " LEFT JOIN `{$this->wpdbAdapter->postmeta}` AS `packetery_pm_restrictions` ON `{$this->wpdbAdapter->posts}`.`ID` = `packetery_pm_restrictions`.`post_id` AND `packetery_pm_restrictions`.`meta_key` = %s", $metaKey );
			$clauses['where'] .= " AND `packetery_pm_restrictions`.`meta_value` IS NOT NULL AND `packetery_pm_restrictions`.`meta_value` != '' AND `packetery_pm_restrictions`.`meta_value` != 'a:0:{}'";
		}

		return $clauses;
	}

	private function getProductForPostId( int $postId ): Entity {
		if ( ! array_key_exists( $postId, self::$productCache ) ) {
			self::$productCache[ $postId ] = $this->productEntityFactory->fromPostId( $postId );
		}

		return self::$productCache[ $postId ];
	}

	/**
	 * @return array<string, bool>
	 */
	private function getEnabledFilters(): array {
		$defaultFilters = [
			self::FILTER_AGE_VERIFICATION      => true,
			self::FILTER_SHIPPING_RESTRICTIONS => true,
		];

		$filters = $this->wpAdapter->applyFilters( 'packetery_product_list_filters_enabled', $defaultFilters );
		if ( ! is_array( $filters ) ) {
			return $defaultFilters;
		}

		$result = $defaultFilters;
		foreach ( $filters as $key => $value ) {
			if ( is_string( $key ) && is_bool( $value ) && array_key_exists( $key, $defaultFilters ) ) {
				$result[ $key ] = $value;
			}
		}

		return $result;
	}
}
