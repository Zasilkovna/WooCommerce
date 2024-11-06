<?php
/**
 * Creates latte cache. Use before generating pot file.
 *
 * @package Packetery
 */

use Packetery\Latte\Engine;
use Packetery\Nette\Utils\Finder;

require_once __DIR__ . '/../../../../wp-includes/wp-db.php';
require_once __DIR__ . '/../../../../wp-includes/rest-api/endpoints/class-wp-rest-controller.php';
require_once __DIR__ . '/../../../../wp-includes/rest-api/class-wp-rest-server.php';

$container   = require __DIR__ . '/../bootstrap-cli.php';
$latteEngine = $container->getByType( Engine::class );

if ( is_dir( $container->parameters['latteTempFolder'] ) ) {
	$filesToDelete = Finder::findFiles( '*' )->from( $container->parameters['latteTempFolder'] );
	foreach ( $filesToDelete as $fileToDelete ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
		unlink( $fileToDelete );
	}
}

$finder = Finder::findFiles( '*.latte' )->from( PACKETERY_PLUGIN_DIR . '/template' );
foreach ( $finder as $file ) {
	$latteEngine->warmupCache( $file );
}
