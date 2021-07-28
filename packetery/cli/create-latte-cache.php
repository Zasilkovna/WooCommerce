<?php

$container = require __DIR__ . '/../bootstrap-cli.php';
/** @var \Latte\Engine $latteEngine */
$latteEngine = $container->getByType(\Latte\Engine::class);

$finder = \Nette\Utils\Finder::findFiles('*.latte')->from(PACKETERY_PLUGIN_DIR . '/template');
foreach ( $finder as $file ) {
	$latteEngine->warmupCache($file);
}
