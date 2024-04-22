<?php

namespace Packetery\Module;

if ( file_exists( WP_PLUGIN_DIR . '/woocommerce/src/Blocks/Integrations/IntegrationInterface.php' ) ) {
    require_once WP_PLUGIN_DIR . '/woocommerce/src/Blocks/Integrations/IntegrationInterface.php';
}

if ( file_exists(WP_PLUGIN_DIR . '/woocommerce/packages/woocommerce-blocks/src/Integrations/IntegrationInterface.php') ) {
    require_once WP_PLUGIN_DIR . '/woocommerce/packages/woocommerce-blocks/src/Integrations/IntegrationInterface.php';
}

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

class PacketaWidgetIntegration implements IntegrationInterface {
	const VERSION = '0.1.0';
	const INTEGRATION_NAME = 'packeta-widget';

	public array $settings;

	public function __construct(array $settings) {
		$this->settings = $settings;
	}

	public function get_name(): string {
		return self::INTEGRATION_NAME;
	}

	public function initialize(): void {
		$scriptPath      = '/packeta/public/js/index.js';
		$scriptAssetPath = PACKETERY_PLUGIN_DIR . '/public/js/index.asset.php';
		$scriptAsset     = file_exists( $scriptAssetPath )
			? require $scriptAssetPath
			: [
				'dependencies' => [],
				'version'      => $this->get_file_version( $scriptPath ),
			];

		wp_register_script(
			self::INTEGRATION_NAME,
			plugins_url( $scriptPath ),
			$scriptAsset['dependencies'],
			$scriptAsset['version'],
			true
		);

		/* todo?
		wp_set_script_translations(
			'packeta-widget',
			'packeta',
			PACKETERY_PLUGIN_DIR . '/languages',
			);
		*/
	}

	public function get_script_handles() {
		return [ self::INTEGRATION_NAME ];
	}

	public function get_editor_script_handles(): array {
		return [ self::INTEGRATION_NAME ];
	}

	public function get_script_data(): array {
		return $this->settings;
	}

	protected function get_file_version( $file ): bool|int|string {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( $file ) ) {
			return filemtime( $file );
		}

		return self::VERSION;
	}
}
