<?php
use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

class PacketaPluginIntegrationOld implements IntegrationInterface {
    public function get_name() {
        return 'packeta-plugin';
    }

    public function initialize() {
        $script_path = '/build/index.js';
        $style_path = '/build/style-index.css';

        $script_url = plugins_url( $script_path, \PacketaPluginAssets::$plugin_file );
        $style_url = plugins_url( $style_path, \PacketaPluginAssets::$plugin_file );

        $script_asset_path = dirname( \PacketaPluginAssets::$plugin_file ) . '/build/index.asset.php';
        $script_asset = file_exists( $script_asset_path )
            ? require $script_asset_path
            : array(
                'dependencies' => array(),
                'version'      => $this->get_file_version( $script_path ),
            );

        wp_enqueue_style(
            'packeta-blocks-integration',
            $style_url,
            [],
            $this->get_file_version( $style_path )
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
            dirname( \PacketaPluginAssets::$plugin_file ) . '/languages'
        );
    }

    public function get_script_handles() {
        return array( 'packeta-blocks-integration' );
    }

    public function get_editor_script_handles() {
        return array( 'packeta-blocks-integration' );
    }

    public function get_script_data() {
        $packeta_plugin_data = some_expensive_serverside_function();
        return [
            'expensive_data_calculation' => $packeta_plugin_data
        ];
    }

    protected function get_file_version( $file ) {
        if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( $file ) ) {
            return filemtime( $file );
        }

        return \PacketaPluginAssets::VERSION;
    }
}
