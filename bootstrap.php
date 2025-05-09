<?php
/**
 * Packeta bootstrap.
 *
 * @package Packeta
 */

use Packetery\Module\CompatibilityBridge;
use Packetery\Module\WpdbTracyPanel;
use Packetery\Nette\Bootstrap\Configurator;
use Packetery\Nette\Http\RequestFactory;
use Packetery\Nette\InvalidArgumentException;
use Packetery\Nette\InvalidStateException;
use Packetery\Tracy\Debugger;

require_once __DIR__ . '/constants.php';

require_once __DIR__ . '/deps/scoper-autoload.php';

$disableGetPostCookieParsing = false;
try {
	( new RequestFactory() )->fromGlobals();
} catch ( InvalidStateException $invalidStateException ) {
	$disableGetPostCookieParsing = true;
} catch ( InvalidArgumentException $invalidArgumentException ) {
}

$configurator = new Configurator();
$configurator->addDynamicParameters(
	[
		'consoleMode' => \PHP_SAPI === 'cli-server' || \PHP_SAPI === 'cli' || (defined( 'WP_CLI' ) && WP_CLI),
		'disableGetPostCookieParsing' => $disableGetPostCookieParsing,
	]
);
$configurator->setDebugMode( PACKETERY_DEBUG );

Debugger::$logDirectory = PACKETERY_PLUGIN_DIR . '/log';
if ( $configurator->isDebugMode() && wp_doing_cron() === false ) {
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
