<?php
/**
 * Creates carrier classes representing shipping methods.
 *
 * @package Packetery
 */

use Packetery\Module\Shipping\ShippingMethodGenerator;
use Packetery\Module\Shipping\ShippingProvider;

require_once __DIR__ . '/../../../../wp-load.php';

$container = require __DIR__ . '/../bootstrap-cli.php';

/**
 * Shipping provider.
 *
 * @var ShippingProvider $shippingProvider
 */
$shippingProvider = $container->getByType( ShippingProvider::class );
$shippingProvider->loadAllClasses();

/**
 * Shipping method generator.
 *
 * @var ShippingMethodGenerator $generator
 */
$generator = $container->getByType( ShippingMethodGenerator::class );
$generator->generateClasses();
