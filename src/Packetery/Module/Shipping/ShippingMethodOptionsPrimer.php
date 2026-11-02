<?php

declare( strict_types=1 );

namespace Packetery\Module\Shipping;

use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\WpdbAdapter;
use stdClass;

/**
 * Loads the instance settings of all Packeta zone methods and the given options in advance.
 * WooCommerce and the plugin then do not read them one by one when they create the methods.
 */
class ShippingMethodOptionsPrimer {
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

	/**
	 * @param string[] $optionNames Options which are read for each Packeta method class.
	 */
	public function prime( array $optionNames ): void {
		$blogId = $this->wpAdapter->getCurrentBlogId();
		if ( isset( $this->primedBlogIds[ $blogId ] ) ) {
			return;
		}
		$this->primedBlogIds[ $blogId ] = true;

		$allOptionNames = array_merge( $this->getInstanceOptionNames(), $optionNames );
		if ( $allOptionNames === [] ) {
			return;
		}

		$this->wpAdapter->primeOptionCaches( $allOptionNames );
	}

	/**
	 * Option names have the same form as WC_Shipping_Method::get_instance_option_key()
	 *
	 * @return string[]
	 */
	private function getInstanceOptionNames(): array {
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
}
