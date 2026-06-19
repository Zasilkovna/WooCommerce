<?php

declare( strict_types=1 );

namespace Packetery\Module\Dashboard;

use Packetery\Module\Carrier;
use Packetery\Module\Carrier\CarrierUpdater;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Order\PacketSynchronizer;
use Packetery\Module\Product;
use Packetery\Module\ProductCategory;
use WP_Query;

class DashboardItemBuilder {

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * @var DashboardHelper
	 */
	private $dashboardHelper;

	/**
	 * @var OptionsProvider
	 */
	private $optionsProvider;

	/**
	 * @var Carrier\EntityRepository
	 */
	private $carrierEntityRepository;

	/**
	 * @var CarrierUpdater
	 */
	private $carrierUpdater;

	/**
	 * @var PacketSynchronizer
	 */
	private $packetSynchronizer;

	public function __construct(
		WpAdapter $wpAdapter,
		DashboardHelper $dashboardHelper,
		OptionsProvider $optionsProvider,
		Carrier\EntityRepository $carrierEntityRepository,
		CarrierUpdater $carrierUpdater,
		PacketSynchronizer $packetSynchronizer
	) {
		$this->wpAdapter               = $wpAdapter;
		$this->dashboardHelper         = $dashboardHelper;
		$this->optionsProvider         = $optionsProvider;
		$this->carrierEntityRepository = $carrierEntityRepository;
		$this->carrierUpdater          = $carrierUpdater;
		$this->packetSynchronizer      = $packetSynchronizer;
	}

	/**
	 * @return DashboardItem[]
	 */
	public function buildItems(): array {
		$newestProductUrl = $this->getNewestProductUrl();

		return [
			new DashboardItem(
				$this->wpAdapter->__( 'Basic settings of the Packeta plugin', 'packeta' ),
				$this->wpAdapter->adminUrl( 'admin.php?page=' . Options\Page::SLUG . '&wizard-enabled=true&wizard-general-settings-tour-enabled=true' ),
				$this->wpAdapter->__( 'Start with this to start using the plugin.', 'packeta' ),
				1,
				$this->optionsProvider->get_api_password() !== null && $this->optionsProvider->get_sender() !== null
			),
			new DashboardItem(
				$this->wpAdapter->__( 'Restrict carriers for products or categories', 'packeta' ),
				$newestProductUrl,
				$this->getDisallowedCarriersDescription( $newestProductUrl ),
				2,
				$this->hasDisallowedCarriers()
			),
			new DashboardItem(
				$this->wpAdapter->__( 'Carriers update', 'packeta' ),
				$this->getCarrierUpdateUrl(),
				$this->wpAdapter->__( 'Load the current list of external carriers so that you can use them.', 'packeta' ),
				3,
				$this->carrierUpdater->getLastUpdate() !== null
			),
			new DashboardItem(
				$this->wpAdapter->__( 'Carrier settings', 'packeta' ),
				$this->wpAdapter->adminUrl( 'admin.php?page=' . Carrier\OptionsPage::SLUG ),
				$this->wpAdapter->__( 'Set prices, weight limits and other settings for the carriers you want to use.', 'packeta' ),
				4,
				count( $this->carrierEntityRepository->getAllActiveCarriersList() ) > 0
			),
			new DashboardItem(
				$this->wpAdapter->__( 'Shipping zone settings', 'packeta' ),
				$this->wpAdapter->adminUrl( 'admin.php?page=wc-settings&tab=shipping' ),
				$this->wpAdapter->__( 'Assign shipping methods to WooCommerce zones so that they are offered at checkout.', 'packeta' ),
				5,
				$this->dashboardHelper->isPacketaShippingMethodActive()
			),
			new DashboardItem(
				$this->wpAdapter->__( 'Set up shipment status tracking', 'packeta' ),
				$this->wpAdapter->adminUrl( 'admin.php?page=' . Options\Page::SLUG . '&tab=' . Options\Page::TAB_PACKET_STATUS_SYNC . '&wizard-enabled=true&wizard-packet-status-tracking-tour-enabled=true' ),
				$this->wpAdapter->__( 'Always keep an eye on the current status of your shipment. This status will be displayed in the order overview.', 'packeta' ),
				6,
				count( $this->optionsProvider->getStatusSyncingPacketStatuses( $this->packetSynchronizer->getDefaultPacketStatuses() ) ) > 0 &&
				count( $this->optionsProvider->getExistingStatusSyncingOrderStatuses() ) > 0
			),
			new DashboardItem(
				$this->wpAdapter->__( 'Set up automatic shipment submission', 'packeta' ),
				$this->wpAdapter->adminUrl( 'admin.php?page=' . Options\Page::SLUG . '&tab=' . Options\Page::TAB_AUTO_SUBMISSION . '&wizard-enabled=true&wizard-auto-submission-tour-enabled=true' ),
				$this->wpAdapter->__( 'Use this if you want to automatically submit shipments based on the order status.', 'packeta' ),
				7,
				$this->optionsProvider->isPacketAutoSubmissionEnabled() &&
				count( $this->optionsProvider->getPacketAutoSubmissionMappedUniqueEvents() ) > 0
			),
			new DashboardItem(
				$this->wpAdapter->__( 'Currency rates', 'packeta' ),
				$this->wpAdapter->adminUrl( 'admin.php?page=' . Options\Page::SLUG . '&tab=' . Options\Page::TAB_CURRENCY_RATES ),
				$this->wpAdapter->__( 'Set custom currency rates for converting packet values when shipping abroad.', 'packeta' ),
				8,
				$this->isCurrencyRatesStepFinished()
			),
			new DashboardItem(
				$this->wpAdapter->__( 'Age verification 18+', 'packeta' ),
				$newestProductUrl,
				$this->getAgeVerificationDescription( $newestProductUrl ),
				9,
				$this->hasProductsWithAgeVerification()
			),
		];
	}

	/**
	 * Newest product whose Packeta product tab is visible (i.e. neither virtual nor downloadable).
	 * Missing _virtual / _downloadable meta is treated as a physical product.
	 */
	private function getNewestProductUrl(): ?string {
		$args = array(
			'post_type'      => 'product',
			'posts_per_page' => 1,
			'orderby'        => 'date',
			'order'          => 'DESC',
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'relation' => 'OR',
					array(
						'key'     => '_virtual',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => '_virtual',
						'value'   => 'yes',
						'compare' => '!=',
					),
				),
				array(
					'relation' => 'OR',
					array(
						'key'     => '_downloadable',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => '_downloadable',
						'value'   => 'yes',
						'compare' => '!=',
					),
				),
			),
		);

		$query = new WP_Query( $args );

		if ( $query->have_posts() ) {
			$query->the_post();
			$editUrl = $this->wpAdapter->getEditPostLink( $this->wpAdapter->getTheId(), '' );
			$this->wpAdapter->resetPostdata();

			return $editUrl;
		}

		return null;
	}

	private function getDisallowedCarriersDescription( ?string $productUrl ): string {
		return $this->wpAdapter->__( 'Disable specific Packeta carriers for individual products or whole product categories.', 'packeta' ) . $this->getNoProductSuffix( $productUrl );
	}

	private function getAgeVerificationDescription( ?string $productUrl ): string {
		return $this->wpAdapter->__( 'Mark products intended for adults only (18+).', 'packeta' ) . $this->getNoProductSuffix( $productUrl );
	}

	private function getNoProductSuffix( ?string $productUrl ): string {
		if ( $productUrl === null ) {
			return ' ' . $this->wpAdapter->__( 'First create at least one product.', 'packeta' );
		}

		return '';
	}

	private function getCarrierUpdateUrl(): ?string {
		if ( $this->optionsProvider->get_api_password() === null ) {
			return null;
		}
		if ( $this->carrierUpdater->getLastUpdate() !== null ) {
			return $this->wpAdapter->adminUrl( 'admin.php?page=' . Carrier\OptionsPage::SLUG . '&update_carriers=1' );
		}

		return $this->wpAdapter->adminUrl( 'admin.php?page=' . DashboardPage::SLUG . '&update_carriers=1' );
	}

	private function hasProductsWithAgeVerification(): bool {
		$args = [
			'post_type'      => 'product',
			'posts_per_page' => 1,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query'     => [
				[
					'key'     => Product\Entity::META_AGE_VERIFICATION_18_PLUS,
					'value'   => '1',
					'compare' => '=',
				],
			],
		];

		$query = new WP_Query( $args );

		return $query->have_posts() === true;
	}

	/**
	 * Disallowed carriers may be set on a product or on a whole product category.
	 */
	private function hasDisallowedCarriers(): bool {
		return $this->hasProductsWithDisallowedCarriers() || $this->hasCategoriesWithDisallowedCarriers();
	}

	private function hasProductsWithDisallowedCarriers(): bool {
		$args = [
			'post_type'      => 'product',
			'posts_per_page' => 1,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query'     => [
				'relation' => 'AND',
				[
					'key'     => Product\Entity::META_DISALLOWED_SHIPPING_RATES,
					'compare' => 'EXISTS',
				],
				[
					'key'     => Product\Entity::META_DISALLOWED_SHIPPING_RATES,
					'value'   => 'a:0:{}',
					'compare' => '!=',
				],
			],
		];

		$query = new WP_Query( $args );

		return $query->have_posts() === true;
	}

	private function hasCategoriesWithDisallowedCarriers(): bool {
		$terms = $this->wpAdapter->getTerms(
			[
				'taxonomy'   => ProductCategory\Entity::TAXONOMY_NAME,
				'hide_empty' => false,
				'number'     => 1,
				'fields'     => 'ids',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'meta_query' => [
					'relation' => 'AND',
					[
						'key'     => ProductCategory\Entity::META_DISALLOWED_SHIPPING_RATES,
						'compare' => 'EXISTS',
					],
					[
						'key'     => ProductCategory\Entity::META_DISALLOWED_SHIPPING_RATES,
						'value'   => 'a:0:{}',
						'compare' => '!=',
					],
				],
			]
		);

		return is_array( $terms ) && count( $terms ) > 0;
	}

	/**
	 * Custom currency rates step is done when the feature is enabled and at least one non-zero rate is filled in.
	 */
	private function isCurrencyRatesStepFinished(): bool {
		if ( ! $this->optionsProvider->isCustomCurrencyRatesEnabled() ) {
			return false;
		}

		foreach ( $this->optionsProvider->getCustomCurrencyRates() as $rate ) {
			if ( $rate !== null && (float) $rate !== 0.0 ) {
				return true;
			}
		}

		return false;
	}
}
