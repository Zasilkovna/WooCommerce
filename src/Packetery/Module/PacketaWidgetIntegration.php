<?php
namespace Packetery\Module;

require_once(WP_PLUGIN_DIR . '/woocommerce/src/Blocks/Integrations/IntegrationInterface.php');
use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

class PacketaWidgetIntegration implements IntegrationInterface {
    const VERSION = '0.1.0';

    private string $pluginDirUrl;

    public function __construct(string $pluginDirUrl)
    {
       $this->pluginDirUrl = $pluginDirUrl;
    }

    public function get_name(): string {
        return 'packeta-widget';
    }

	public function initialize(): void {
        $scriptPath = 'public/js/index.js';
        $scriptAssetPath = PACKETERY_PLUGIN_DIR . '/public/js/packetaWidget.asset.php';
        $scriptAsset = file_exists( $scriptAssetPath )
            ? require $scriptAssetPath
            : array(
                'dependencies' => array(),
                'version'      => $this->get_file_version( $scriptPath ),
            );

        /*
        wp_register_script(
            'packeta-widget',
            $this->pluginDirUrl . $scriptPath,
            $scriptAsset['dependencies'],
            $scriptAsset['version'],
            true
        );

        wp_set_script_translations(
            'packeta-widget',
        );
        */
    }

    public function get_script_handles() {
        return array( 'packeta-widget-integration' );
    }

    public function get_editor_script_handles(): array
    {
        return array( 'packeta-widget-integration' );
    }

    public function get_script_data(): array
    {
        return [];
    }

    protected function get_file_version( $file ): bool|int|string
    {
        if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( $file ) ) {
            return filemtime( $file );
        }

        return self::VERSION;
    }
}
