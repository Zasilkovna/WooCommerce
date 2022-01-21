<?php

use Packetery\Module\CompatibilityBridge;
use PacketeryNette\Bootstrap\Configurator;

defined( 'PACKETERY_PLUGIN_DIR' ) || define( 'PACKETERY_PLUGIN_DIR', __DIR__ );
defined( 'PACKETERY_DEBUG' ) || define( 'PACKETERY_DEBUG', false );

require_once __DIR__ . '/packetery_vendor/autoload.php';

$configurator = new Configurator();
$configurator->setDebugMode( PACKETERY_DEBUG );

if ( PACKETERY_DEBUG ) {
	$configurator->enableDebugger();
}

$configurator->addConfig( __DIR__ . '/config/config.neon' );
$configurator->setTempDirectory( __DIR__ . '/temp' );
$configurator->createRobotLoader()->addDirectory( __DIR__ . '/src' )->register();

$configurator->defaultExtensions = [
	'http'    => $configurator->defaultExtensions['http'],
];

$container = $configurator->createContainer();
CompatibilityBridge::setContainer( $container );

return $container;
