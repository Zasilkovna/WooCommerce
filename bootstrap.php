<?php
/**
 * Packeta bootstrap.
 *
 * @package Packeta
 */

use Packetery\Module\CompatibilityBridge;
use Packetery\Module\Helper;
use Packetery\Module\WpdbTracyPanel;
use PacketeryNette\Bootstrap\Configurator;
use PacketeryTracy\Debugger;

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/packetery_vendor/autoload.php';

require_once __DIR__ . '/src/Packetery/Module/Helper.php';
Helper::transformGlobalCookies();

$configurator = new Configurator();
$configurator->setDebugMode( PACKETERY_DEBUG );

Debugger::$logDirectory = PACKETERY_PLUGIN_DIR . '/log';
if ( PACKETERY_DEBUG && false === wp_doing_cron() ) {
	$configurator->enableDebugger( Debugger::$logDirectory );
	Debugger::$strictMode = false;
}

$configurator->addConfig( __DIR__ . '/config/config.neon' );
$configurator->setTempDirectory( __DIR__ . '/temp' );
$configurator->createRobotLoader()->addDirectory( __DIR__ . '/src' )->setAutoRefresh( false )->register();

$configurator->defaultExtensions = [];

$container = $configurator->createContainer();
CompatibilityBridge::setContainer( $container );

if ( Debugger::isEnabled() ) {
	Debugger::getBar()->addPanel( $container->getByType( WpdbTracyPanel::class ) );
}

return $container;
