<?php
/**
 * Packeta bootstrap.
 *
 * @package Packeta
 */

use Packetery\Module\CompatibilityBridge;
use Packetery\Module\Helper;
use PacketeryNette\Bootstrap\Configurator;

defined( 'PACKETERY_PLUGIN_DIR' ) || define( 'PACKETERY_PLUGIN_DIR', __DIR__ );
defined( 'PACKETERY_DEBUG' ) || define( 'PACKETERY_DEBUG', false );

require_once __DIR__ . '/packetery_vendor/autoload.php';

require_once __DIR__ . '/src/Packetery/Module/Helper.php';
Helper::transformGlobalCookies();

$configurator = new Configurator();
$configurator->setDebugMode( PACKETERY_DEBUG );

if ( PACKETERY_DEBUG && false === wp_doing_cron() ) {
	$configurator->enableDebugger( PACKETERY_PLUGIN_DIR . '/log' );
	\PacketeryTracy\Debugger::$strictMode = false;
}

$configurator->addConfig( __DIR__ . '/config/config.neon' );
$configurator->setTempDirectory( __DIR__ . '/temp' );
$configurator->createRobotLoader()->addDirectory( __DIR__ . '/src' )->setAutoRefresh( false )->register();

$configurator->defaultExtensions = [];

$container = $configurator->createContainer();
CompatibilityBridge::setContainer( $container );

return $container;
