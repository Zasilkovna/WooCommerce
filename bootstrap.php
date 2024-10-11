<?php
/**
 * Packeta bootstrap.
 *
 * @package Packeta
 */

use Packetery\Module\CompatibilityBridge;
use Packetery\Module\ModuleHelper;
use Packetery\Module\WpdbTracyPanel;
use Packetery\Nette\Bootstrap\Configurator;
use Packetery\Tracy\Debugger;

require_once __DIR__ . '/constants.php';

require_once __DIR__ . '/deps/scoper-autoload.php';

require_once __DIR__ . '/src/Packetery/Module/ModuleHelper.php';
ModuleHelper::transformGlobalCookies();

$configurator = new Configurator();
$configurator->setDebugMode( PACKETERY_DEBUG );

Debugger::$logDirectory = PACKETERY_PLUGIN_DIR . '/log';
if ( $configurator->isDebugMode() && false === wp_doing_cron() ) {
	$configurator->enableDebugger( Debugger::$logDirectory );
	Debugger::$strictMode = false;
}

$configurator->addConfig( __DIR__ . '/config/config.neon' );

$localConfigFile = __DIR__ . '/config/config.local.neon';
if ( file_exists( $localConfigFile ) ) {
	$configurator->addConfig( $localConfigFile ); // Local Development ENV only!
}

$configurator->setTempDirectory( __DIR__ . '/temp' );
$configurator->createRobotLoader()->addDirectory( __DIR__ . '/src' )->setAutoRefresh( false )->register();

$configurator->defaultExtensions = [];

$container = $configurator->createContainer();
CompatibilityBridge::setContainer( $container );

if ( Debugger::isEnabled() ) {
	Debugger::getBar()->addPanel( $container->getByType( WpdbTracyPanel::class ) );
}

return $container;
