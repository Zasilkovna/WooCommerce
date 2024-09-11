<?php

declare( strict_types=1 );

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/php-stubs/wordpress-stubs/wordpress-stubs.php';
require_once __DIR__ . '/../vendor/php-stubs/woocommerce-stubs/woocommerce-stubs.php';
require_once __DIR__ . '/../vendor/php-stubs/woocommerce-stubs/woocommerce-packages-stubs.php';
require_once __DIR__ . '/../deps/scoper-autoload.php';

if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}
