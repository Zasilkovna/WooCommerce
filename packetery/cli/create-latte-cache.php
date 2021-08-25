<?php
/**
 * Creates latte cache. Use before generating pot file.
 *
 * @package Packetery
 */

$container    = require __DIR__ . '/../bootstrap-cli.php';
$latte_engine = $container->getByType( \PacketeryLatte\Engine::class );

if ( is_dir( $container->parameters['latteTempFolder'] ) ) {
	$files_to_delete = \PacketeryNette\Utils\Finder::findFiles( '*' )->from( $container->parameters['latteTempFolder'] );
	foreach ( $files_to_delete as $file_to_delete ) {
		unlink( $file_to_delete );
	}
}

$finder = \PacketeryNette\Utils\Finder::findFiles( '*.latte' )->from( PACKETERY_PLUGIN_DIR . '/template' );
foreach ( $finder as $file ) {
	$latte_engine->warmupCache( $file );
}
