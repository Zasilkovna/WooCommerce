<?php
/**
 * Run scoper script.
 *
 * @package Packetery
 */

use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Script\Event;
use Wpify\Scoper\Plugin;

require_once __DIR__ . '/../vendor/autoload.php';

$command = null;
if ( isset( $argv[1] ) && 'install' === $argv[1] ) {
	$command = Plugin::SCOPER_INSTALL_CMD;
}
if ( isset( $argv[1] ) && 'update' === $argv[1] ) {
	$command = Plugin::SCOPER_UPDATE_CMD;
}
if ( null === $command ) {
	echo 'Usage: wpify-scoper [command]' . PHP_EOL;
	echo '  commands:' . PHP_EOL;
	echo '    update' . PHP_EOL;
	echo '    install' . PHP_EOL . PHP_EOL;
	exit;
}

echo "Constructing composer and event...\n";
$factory    = new Factory();
$ioInterace = new NullIO();
$composer   = $factory->createComposer( $ioInterace );
$fakeEvent  = new Event(
	$command,
	$composer,
	$ioInterace
);

$scoper = new Plugin();
$scoper->activate( $composer, $ioInterace );
echo "Execute...\n";
$scoper->execute( $fakeEvent );
