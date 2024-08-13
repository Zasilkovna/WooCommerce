<?php
/**
 * WidgetIntegration.
 *
 * @package Packetery
 */

namespace Packetery\Module\Blocks;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use Packetery\Module\Plugin;

if ( file_exists( WP_PLUGIN_DIR . '/woocommerce/src/Blocks/Integrations/IntegrationInterface.php' ) ) {
	require_once WP_PLUGIN_DIR . '/woocommerce/src/Blocks/Integrations/IntegrationInterface.php';
}

if ( file_exists( WP_PLUGIN_DIR . '/woocommerce/packages/woocommerce-blocks/src/Integrations/IntegrationInterface.php' ) ) {
	require_once WP_PLUGIN_DIR . '/woocommerce/packages/woocommerce-blocks/src/Integrations/IntegrationInterface.php';
}

/**
 * WidgetIntegration.
 *
 * @package Packetery
 */
class WidgetIntegration implements IntegrationInterface {
	const INTEGRATION_NAME = 'packeta-widget';

	/**
	 * Settings.
	 *
	 * @var array
	 */
	public $settings;

	/**
	 * Constructor.
	 *
	 * @param array $settings Settings.
	 */
	public function __construct( array $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Gets name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return self::INTEGRATION_NAME;
	}

	/**
	 * Initializes.
	 *
	 * @return void
	 */
	public function initialize(): void {
		$scriptPath      = '/packeta/public/block/index.js';
		$scriptAssetPath = PACKETERY_PLUGIN_DIR . '/public/block/index.asset.php';
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
	}

	/**
	 * Gets script handles.
	 *
	 * @return string[]
	 */
	public function get_script_handles() {
		return [ self::INTEGRATION_NAME ];
	}

	/**
	 * Gets editor script handles.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles(): array {
		return [ self::INTEGRATION_NAME ];
	}

	/**
	 * Gets script data.
	 *
	 * @return array
	 */
	public function get_script_data(): array {
		return $this->settings;
	}

	/**
	 * Gets file version.
	 *
	 * @param string $filePath File path.
	 *
	 * @return bool|int|string
	 */
	protected function get_file_version( string $filePath ) {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( $filePath ) ) {
			return filemtime( $filePath );
		}

		return Plugin::VERSION;
	}
}
