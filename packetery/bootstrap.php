<?php

define('PACKETERY_PLUGIN_DIR', __DIR__);

require_once __DIR__ . '/vendor/autoload.php';

$configurator = new \Nette\Bootstrap\Configurator();
$configurator->setDebugMode( WP_DEBUG );
$configurator->addConfig( __DIR__ . '/config/config.neon' );
$configurator->setTempDirectory( __DIR__ . '/temp' );

$configurator->createRobotLoader()->addDirectory( __DIR__ . '/src' )->register();

if ( class_exists( Tracy\Debugger::class ) ) {
	Tracy\Debugger::enable( Tracy\Debugger::DEVELOPMENT );
}

return $configurator->createContainer();
