<?php

declare( strict_types=1 );

$args = $_SERVER['argv'] ?? [];

$currentTestsuite = null;
$testsuiteIndex   = array_search( '--testsuite', $args, true );
if ( $testsuiteIndex !== false && isset( $args[ $testsuiteIndex + 1 ] ) ) {
	$currentTestsuite = $args[ $testsuiteIndex + 1 ];
}

if ( $currentTestsuite === null ) {
	foreach ( $args as $arg ) {
		if ( strpos( $arg, '--testsuite=' ) === 0 ) {
			$currentTestsuite = substr( $arg, strlen( '--testsuite=' ) );

			break;
		}
	}
}

$allowedTestsuites = [ 'unit', 'integration' ];

if ( ! in_array( $currentTestsuite, $allowedTestsuites, true ) ) {
	die( "Invalid testsuite provided or testsuite detection failed.\n" );
}

if ( $currentTestsuite === 'unit' ) {
	require_once __DIR__ . '/../vendor/autoload.php';
	require_once __DIR__ . '/../vendor/php-stubs/wordpress-stubs/wordpress-stubs.php';
	require_once __DIR__ . '/../vendor/php-stubs/woocommerce-stubs/woocommerce-stubs.php';
	require_once __DIR__ . '/../vendor/php-stubs/woocommerce-stubs/woocommerce-packages-stubs.php';
	require_once __DIR__ . '/../deps/scoper-autoload.php';
	require_once __DIR__ . '/../constants.php';
	// To import constants.
	require_once __DIR__ . '/../vendor/szepeviktor/phpstan-wordpress/bootstrap.php';
}

if ( $currentTestsuite === 'integration' ) {
	// TODO later: install demo web and plugin from zip

	function loadConfigFromInstallScript( $filePath ): array {
		if ( ! file_exists( $filePath ) ) {
			throw new RuntimeException( "Configuration file not found: $filePath" );
		}

		$content = file_get_contents( $filePath );
		if ( $content === false ) {
			throw new RuntimeException( "Could not read configuration file: $filePath" );
		}

		$config = [];
		$lines  = explode( "\n", $content );

		foreach ( $lines as $line ) {
			if ( preg_match( '/^([A-Z_]+)="(.*)"$/', $line, $matches ) ) {
				$config[ $matches[1] ] = $matches[2];
			}
		}

		return $config;
	}

	$demoInstallDirectory     = __DIR__ . '/../cli/demo-install/';
	$demoInstallConfiguration = $demoInstallDirectory . 'install-wpwc.sh';
	if ( is_file( $demoInstallDirectory . 'install-wpwc.config.local.sh' ) ) {
		$demoInstallConfiguration = $demoInstallDirectory . 'install-wpwc.config.local.sh';
	}
	$configuration = loadConfigFromInstallScript( $demoInstallConfiguration );

	if ( ! is_file( $configuration['NEW_WP_ROOT'] . '/wp-config.php' ) ) {
		throw new RuntimeException( 'WordPress installation not found in ' . $configuration['NEW_WP_ROOT'] );
	}

	$pluginDir      = $configuration['NEW_WP_ROOT'] . '/wp-content/plugins/packeta/';
	$mainPluginFile = $pluginDir . 'packeta.php';
	if ( ! is_file( $mainPluginFile ) ) {
		throw new RuntimeException( 'Packeta plugin installation not found in ' . $configuration['NEW_WP_ROOT'] );
	}

	// Force WordPress to use the direct filesystem method instead of FTP - prevent `ftp_nlist(): Argument #1 ($ftp) must be of type FTP\Connection, null given`
	if ( ! defined( 'FS_METHOD' ) ) {
		define( 'FS_METHOD', 'direct' );
	}

	require_once $configuration['NEW_WP_ROOT'] . '/wp-load.php';
}
