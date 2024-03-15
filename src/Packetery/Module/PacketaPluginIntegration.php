<?php
namespace Packetery\Module;

require_once(WP_PLUGIN_DIR . '/woocommerce/src/Blocks/Integrations/IntegrationInterface.php');
use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

class PacketaPluginIntegration implements IntegrationInterface {
    const VERSION = '1.0.0';

    private $pluginAssets;

    public function __construct(\Packetery\Module\PacketaPluginAssets $pluginAssets) {
        $this->pluginAssets = $pluginAssets;
    }

    public function get_name() {
        return 'packeta-plugin';
    }

	public function initialize(): void {
    $scriptFiles = $this->pluginAssets->getScriptFiles();
    $styleFiles = $this->pluginAssets->getStyleFiles();

    foreach ($scriptFiles as $scriptFile) {
        $script_url = plugins_url( $scriptFile);
        $script_asset_path = dirname( $scriptFile );
        $script_asset = file_exists( $script_asset_path )
            ? require $script_asset_path
            : array(
                'dependencies' => array(),
                'version'      => $this->get_file_version( $scriptFile ),
            );

        wp_register_script(
            'packeta-blocks-integration',
            $script_url,
            $script_asset['dependencies'],
            $script_asset['version'],
            true
        );
        wp_set_script_translations(
            'packeta-blocks-integration',
            'packeta-plugin',
            dirname( $scriptFile ) . '/languages'
        );
    }

    foreach ($styleFiles as $styleFile) {
        $style_url = plugins_url( $styleFile );

        wp_enqueue_style(
            'packeta-blocks-integration',
            $style_url,
            [],
            $this->get_file_version( $styleFile )
        );
    }
}
    public function get_script_handles() {
        return array( 'packeta-blocks-integration' );
    }

    public function get_editor_script_handles() {
        return array( 'packeta-blocks-integration' );
    }

    public function get_script_data() {
        //$packeta_plugin_data = some_expensive_serverside_function();
        $packeta_plugin_data = ['abrakadabra' => 'hokus pokus', 'carymaryfuk' => 'open sesame'];
        return [
            'expensive_data_calculation' => $packeta_plugin_data
        ];
    }

    protected function get_file_version( $file ) {
        if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( $file ) ) {
            return filemtime( $file );
        }

        return self::VERSION;
    }
}
