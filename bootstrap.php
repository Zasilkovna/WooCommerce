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

require_once __DIR__ . '/src/Packetery/Module/Helper.php';
require_once __DIR__ . '/src/Packetery/Module/Upgrade.php';
require_once __DIR__ . '/src/Packetery/Module/Plugin.php';
Helper::transformGlobalCookies();

$cacheBasePathConstantName = 'PACKETERY_CACHE_BASE_PATH';

if (defined($cacheBasePathConstantName)) {
    $tempDir = constant($cacheBasePathConstantName);
} else {
    $tempDir = __DIR__ . '/temp';
}

$cacheDir = $tempDir . '/cache';

$oldVersion = get_option( Packetery\Module\Upgrade::VERSION_OPTION_NAME );
if ( Plugin::VERSION !== $oldVersion ) {
    Helper::deleteOldVersionCache($cacheDir);
}

$configurator = new Configurator();
$configurator->setDebugMode( PACKETERY_DEBUG );

Debugger::$logDirectory = PACKETERY_PLUGIN_DIR . '/log';
if ( PACKETERY_DEBUG && false === wp_doing_cron() ) {
    $configurator->enableDebugger( Debugger::$logDirectory );
    Debugger::$strictMode = false;
}

$configurator->addConfig( __DIR__ . '/config/config.neon' );

$localConfigFile = __DIR__ . '/config/config.local.neon';
if ( file_exists( $localConfigFile ) ) {
    $configurator->addConfig( $localConfigFile ); // Local Development ENV only!
}

$configurator->setTempDirectory($tempDir);
$configurator->createRobotLoader()->addDirectory( __DIR__ . '/src' )->setAutoRefresh( false )->register();

$configurator->defaultExtensions = [];

$configurator->addStaticParameters(['cacheDir' => $cacheDir]);

$container = $configurator->createContainer();
CompatibilityBridge::setContainer( $container );

if ( Debugger::isEnabled() ) {
	Debugger::getBar()->addPanel( $container->getByType( WpdbTracyPanel::class ) );
}

return $container;
