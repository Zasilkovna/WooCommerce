<?php
/**
 * Packeta bootstrap.
 *
 * @package Packeta
 */

use Packetery\Module\CompatibilityBridge;
use Packetery\Module\ModuleHelper;
use Packetery\Module\Options\OptionNames;
use Packetery\Module\Plugin;
use Packetery\Module\WpdbTracyPanel;
use Packetery\Nette\Bootstrap\Configurator;
use Packetery\Nette\Http\RequestFactory;
use Packetery\Nette\InvalidStateException;
use Packetery\Nette\Utils\FileSystem;
use Packetery\Tracy\Debugger;

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/deps/scoper-autoload.php';

$disableGetPostCookieParsing = false;
if ( PHP_SAPI !== 'cli' ) {
	try {
		( new RequestFactory() )->fromGlobals();
	} catch ( InvalidStateException $invalidStateException ) {
		$disableGetPostCookieParsing = true;
	}
}

$cacheBasePathConstantName = 'PACKETERY_CACHE_BASE_PATH';

if ( defined( $cacheBasePathConstantName ) ) {
	$tempDir = constant( $cacheBasePathConstantName );
	Filesystem::createDir( $tempDir, 0775 );
	$logBaseDir = $tempDir;
} else {
	$tempDir    = __DIR__ . '/temp';
	$logBaseDir = PACKETERY_PLUGIN_DIR;
}

$configurator = new Configurator();

$configurator->defaultExtensions = [];
$configurator->setDebugMode( PACKETERY_DEBUG );
$configurator->setTempDirectory( $tempDir );
$configurator->createRobotLoader()
	->addDirectory( __DIR__ . '/src' )
	->setAutoRefresh( false )
	->register();

$cacheDir = $tempDir . '/cache';
Filesystem::createDir( $cacheDir, 0775 );

$oldVersion = get_option( OptionNames::VERSION );
if ( $oldVersion !== Plugin::VERSION ) {
	ModuleHelper::instantDelete( $cacheDir );
}

$configurator->addStaticParameters(
	[
		'cacheDir' => $cacheDir,
	]
);
$configurator->addDynamicParameters(
	[
		'disableGetPostCookieParsing' => $disableGetPostCookieParsing,
	]
);

Debugger::$logDirectory = $logBaseDir . '/log';
Filesystem::createDir( Debugger::$logDirectory, 0775 );

if ( $configurator->isDebugMode() && wp_doing_cron() === false ) {
	$configurator->enableDebugger( Debugger::$logDirectory );
	Debugger::$strictMode = false;
}

$configurator->addConfig( __DIR__ . '/config/config.neon' );

$localConfigFile = __DIR__ . '/config/config.local.neon';
if ( file_exists( $localConfigFile ) ) {
	$configurator->addConfig( $localConfigFile ); // Local Development ENV only!
}

$wpContentConfigDir = WP_CONTENT_DIR . '/packeta/config/';
if ( is_dir( $wpContentConfigDir ) ) {
	$configFiles = glob( $wpContentConfigDir . '*.neon' );
	if ( $configFiles !== false ) {
		foreach ( $configFiles as $configFile ) {
			$configurator->addConfig( $configFile );
		}
	}
}

$container = $configurator->createContainer();
CompatibilityBridge::setContainer( $container );

if ( Debugger::isEnabled() ) {
	Debugger::getBar()->addPanel( $container->getByType( WpdbTracyPanel::class ) );
}

return $container;
