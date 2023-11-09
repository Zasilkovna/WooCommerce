<?php

use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Script\Event;
use Wpify\Scoper\Plugin;

require_once __DIR__ . '/../vendor/autoload.php';

echo "Constructing composer and event...\n";
$factory    = new Factory();
$ioInterace = new NullIO();
$composer   = $factory->createComposer( $ioInterace );
$fakeEvent  = new Event(
	Plugin::SCOPER_UPDATE_CMD,
	$composer,
	$ioInterace
);

$scoper = new Plugin();
$scoper->activate( $composer, $ioInterace );
echo "Execute...\n";
$scoper->execute( $fakeEvent );
