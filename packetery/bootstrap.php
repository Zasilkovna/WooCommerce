<?php

define( 'PACKETERY_PLUGIN_DIR', __DIR__ );

require_once __DIR__ . '/packetery_vendor/autoload.php';

$configurator = new \PacketeryNette\Bootstrap\Configurator();
$configurator->setDebugMode( WP_DEBUG );

if ( getenv( 'PACKETERY_DEBUG' ) === '1' ) {
	$configurator->enableDebugger();
}

$configurator->addConfig( __DIR__ . '/config/config.neon' );
$configurator->setTempDirectory( __DIR__ . '/temp' );
$configurator->createRobotLoader()->addDirectory( __DIR__ . '/src' )->register();

return $configurator->createContainer();
