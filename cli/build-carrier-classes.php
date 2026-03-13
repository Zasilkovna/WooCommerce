<?php
/**
 * Creates carrier classes representing shipping methods.
 *
 * @package Packetery
 */

use Packetery\Module\Shipping\ShippingMethodBulkGenerator;
use Packetery\Module\Shipping\ShippingProvider;

require_once __DIR__ . '/../../../../wp-load.php';

$container = require __DIR__ . '/../bootstrap-cli.php';

ShippingProvider::loadAllClasses();

/**
 * @var ShippingMethodBulkGenerator $generator
 */
$generator = $container->getByType( ShippingMethodBulkGenerator::class );
$generator->generateClasses();
