<?php
namespace Packetery\Module;
class PacketaExtendStoreEndpoint {
    private static $extend;
    const IDENTIFIER = 'packeta';

    public static function init( $extend_rest_api ) {
        self::$extend = $extend_rest_api;
        self::extend_store();
    }

    public static function extend_store() {
        // Register into `cart/items`
        self::$extend->register_endpoint_data(
            array(
                'endpoint'        => 'cart/items', // Replace with your endpoint
                'namespace'       => self::IDENTIFIER,
                'data_callback'   => array( 'PacketaExtendStoreEndpoint', 'extend_cart_item_data' ),
                'schema_callback' => array( 'PacketaExtendStoreEndpoint', 'extend_cart_item_schema' ),
                'schema_type'     => ARRAY_A,
            )
        );
    }

    public static function extend_cart_item_data( $cart_item ) {
        $item_data = array(
            'sample_data' => 'This is a sample data',
        );

        return $item_data;
    }

    public static function extend_cart_item_schema() {
        return array(
            'sample_data' => array(
                'description' => __( 'This is a sample data.', 'packeta' ),
                'type'        => array( 'string', 'null' ),
                'context'     => array( 'view', 'edit' ),
                'readonly'    => true,
            ),
        );
    }
}
