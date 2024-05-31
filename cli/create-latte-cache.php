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

/** @var Engine $latte_engine */
$latte_engine = $container->getByType( Engine::class );

$latteTempFolder = $container->parameters['latteTempFolder'];
if ( is_dir($latteTempFolder) ) {
    \Packetery\Module\Helper::instantDelete($latteTempFolder);
}

$finder = Finder::findFiles( '*.latte' )->from( PACKETERY_PLUGIN_DIR . '/template' );
foreach ( $finder as $file ) {
	$latte_engine->warmupCache( $file );
}
