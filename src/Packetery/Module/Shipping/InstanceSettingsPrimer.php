<?php

declare( strict_types=1 );

namespace Packetery\Module\Shipping;

use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\WpdbAdapter;
use stdClass;

/**
 * Loads the instance settings of all Packeta zone methods with one query.
 * WooCommerce then does not read them one by one when it creates the methods.
 */
class InstanceSettingsPrimer {
	private const OPTIONS_CACHE_GROUP   = 'options';
	private const NOT_OPTIONS_CACHE_KEY = 'notoptions';

	/**
	 * @var WpdbAdapter
	 */
	private $wpdbAdapter;

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * The options cache is per blog, so each blog is primed once
	 *
	 * @var array<int, true>
	 */
	private $primedBlogIds = [];

	public function __construct( WpdbAdapter $wpdbAdapter, WpAdapter $wpAdapter ) {
		$this->wpdbAdapter = $wpdbAdapter;
		$this->wpAdapter   = $wpAdapter;
	}

	public function prime(): void {
		$blogId = $this->wpAdapter->getCurrentBlogId();
		if ( isset( $this->primedBlogIds[ $blogId ] ) ) {
			return;
		}
		$this->primedBlogIds[ $blogId ] = true;

		$optionNames = $this->getOptionNames();
		if ( $optionNames === [] ) {
			return;
		}

		if ( $this->wpAdapter->canPrimeOptionCaches() ) {
			$this->wpAdapter->primeOptionCaches( $optionNames );

			return;
		}

		$this->primeWithOwnQuery( $optionNames );
	}

	/**
	 * Option names have the same form as WC_Shipping_Method::get_instance_option_key()
	 *
	 * @return string[]
	 */
	private function getOptionNames(): array {
		$zoneMethodsTable = $this->wpdbAdapter->getShippingZoneMethodsTable();

		/**
		 * @var stdClass[]|null $rows
		 */
		$rows = $this->wpdbAdapter->get_results(
			$this->wpdbAdapter->prepare(
				"SELECT `method_id`, `instance_id` FROM `{$zoneMethodsTable}` WHERE `method_id` LIKE %s",
				$this->wpdbAdapter->escLike( BaseShippingMethod::PACKETA_METHOD_PREFIX ) . '%'
			)
		);

		$optionNames = [];
		foreach ( $rows ?? [] as $row ) {
			// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
			$optionNames[] = "woocommerce_{$row->method_id}_{$row->instance_id}_settings";
		}

		return $optionNames;
	}

	/**
	 * Does the same as wp_prime_option_caches(), which WordPress has since 6.4.
	 * The cache keeps the raw option value, get_option() unserializes it.
	 *
	 * @param string[] $optionNames
	 */
	private function primeWithOwnQuery( array $optionNames ): void {
		$namesToLoad = $this->getNamesNotInCache( $optionNames );
		if ( $namesToLoad === [] ) {
			return;
		}

		$optionsTable = $this->wpdbAdapter->getOptionsTable();
		$inClause     = $this->wpdbAdapter->prepareInClause( $namesToLoad );

		/**
		 * @var stdClass[]|null $rows
		 */
		$rows = $this->wpdbAdapter->get_results(
			"SELECT `option_name`, `option_value` FROM `{$optionsTable}` WHERE `option_name` IN ({$inClause})"
		);

		$foundNames = [];
		foreach ( $rows ?? [] as $row ) {
			// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
			$this->wpAdapter->cacheAdd( $row->option_name, $row->option_value, self::OPTIONS_CACHE_GROUP );
			// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
			$foundNames[] = $row->option_name;
		}

		$missingNames = array_diff( $namesToLoad, $foundNames );
		if ( $missingNames === [] ) {
			return;
		}

		$notOptions = $this->getNotOptions();
		foreach ( $missingNames as $missingName ) {
			$notOptions[ $missingName ] = true;
		}
		$this->wpAdapter->cacheSet( self::NOT_OPTIONS_CACHE_KEY, $notOptions, self::OPTIONS_CACHE_GROUP );
	}

	/**
	 * @param string[] $optionNames
	 *
	 * @return string[]
	 */
	private function getNamesNotInCache( array $optionNames ): array {
		$allOptions    = $this->wpAdapter->loadAllOptions();
		$cachedOptions = $this->wpAdapter->cacheGetMultiple( $optionNames, self::OPTIONS_CACHE_GROUP );
		$notOptions    = $this->getNotOptions();

		$namesNotInCache = [];
		foreach ( $optionNames as $optionName ) {
			$isCached = isset( $allOptions[ $optionName ] ) ||
				isset( $notOptions[ $optionName ] ) ||
				( isset( $cachedOptions[ $optionName ] ) && $cachedOptions[ $optionName ] !== false );
			if ( ! $isCached ) {
				$namesNotInCache[] = $optionName;
			}
		}

		return $namesNotInCache;
	}

	/**
	 * @return array<mixed>
	 */
	private function getNotOptions(): array {
		$notOptions = $this->wpAdapter->cacheGet( self::NOT_OPTIONS_CACHE_KEY, self::OPTIONS_CACHE_GROUP );

		return is_array( $notOptions ) ? $notOptions : [];
	}
}
