<?php

use Packetery\Module\Uninstaller;

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

$container = require __DIR__ . '/bootstrap.php';
$container->getByType( Uninstaller::class )->uninstall();
