<?php

declare( strict_types=1 );

namespace Packetery;

class Plugin {

	public const DOMAIN = 'packetery';

	/** @var \Packetery\Options\Page */
	private $options_page;

	/**
	 * Plugin constructor.
	 *
	 * @param Options\Page $optionsPage Options page.
	 */
	public function __construct( Options\Page $optionsPage ) {
		$this->options_page = $optionsPage;
	}

	public function run() {
		add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
	}

	public function add_menu_pages() {
		$this->options_page->register();
	}

	/**
	 * @param array $methods
	 *
	 * @return array
	 */
	public static function add_shipping_method( array $methods ): array {
		$methods['packetery_shipping_method'] = \WC_Packetery_Shipping_Method::class;

		return $methods;
	}

}
