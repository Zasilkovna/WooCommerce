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

$container    = require __DIR__ . '/../bootstrap-cli.php';
$latte_engine = $container->getByType( Engine::class );

if ( is_dir( $container->parameters['latteTempFolder'] ) ) {
	$files_to_delete = Finder::findFiles( '*' )->from( $container->parameters['latteTempFolder'] );
	foreach ( $files_to_delete as $file_to_delete ) {
		unlink( $file_to_delete );
	}
}

$finder = Finder::findFiles( '*.latte' )->from( PACKETERY_PLUGIN_DIR . '/template' );
foreach ( $finder as $file ) {
	$latte_engine->warmupCache( $file );
}
