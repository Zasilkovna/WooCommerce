<?php
/**
 * Packeta bootstrap.
 *
 * @package Packeta
 */

use Packetery\Module\CompatibilityBridge;
use Packetery\Module\Helper;
use Packetery\Module\Plugin;
use Packetery\Module\WpdbTracyPanel;
use Packetery\Nette\Bootstrap\Configurator;
use Packetery\Tracy\Debugger;

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/deps/scoper-autoload.php';

$cacheBasePathConstantName = 'PACKETERY_CACHE_BASE_PATH';

if (defined( $cacheBasePathConstantName )) {
    $tempDir = constant($cacheBasePathConstantName);
    $logBaseDir = $tempDir;

} else {
    $tempDir = __DIR__ . '/temp';
    $logBaseDir = PACKETERY_PLUGIN_DIR;
}

$configurator = new Configurator();

$configurator->defaultExtensions = [];
$configurator->setDebugMode( PACKETERY_DEBUG );
$configurator->setTempDirectory($tempDir);
$configurator->createRobotLoader()
    ->addDirectory( __DIR__ . '/src' )
    ->setAutoRefresh( false )
    ->register();

Helper::transformGlobalCookies();

$cacheDir = $tempDir . '/cache';

$oldVersion = get_option( Packetery\Module\Upgrade::VERSION_OPTION_NAME );
if ( Plugin::VERSION !== $oldVersion ) {
    Helper::instantDelete($cacheDir);
}

Debugger::$logDirectory = $logBaseDir . '/log';
if ( PACKETERY_DEBUG && false === wp_doing_cron() ) {
    $configurator->enableDebugger( Debugger::$logDirectory );
    Debugger::$strictMode = false;
}

$configurator->addConfig( __DIR__ . '/config/config.neon' );

$localConfigFile = __DIR__ . '/config/config.local.neon';
if ( file_exists( $localConfigFile ) ) {
    $configurator->addConfig( $localConfigFile ); // Local Development ENV only!
}

$configurator->addStaticParameters(['cacheDir' => $cacheDir]);

$container = $configurator->createContainer();
CompatibilityBridge::setContainer( $container );

if ( Debugger::isEnabled() ) {
	Debugger::getBar()->addPanel( $container->getByType( WpdbTracyPanel::class ) );
}

return $container;
