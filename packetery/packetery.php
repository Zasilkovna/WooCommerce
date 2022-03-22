<?php
/**
 * Plugin implementing Packeta shipping methods.
 *
 * @package   Packeta
 *
 * @wordpress-plugin
 *
 * Plugin Name: Packeta
 * Description: With the help of our official plugin, You can choose pickup points of Packeta and its external carriers in all of Europe, or utilize address delivery to 25 countries in the European Union, straight from the cart in Your e-shop. You can also submit all your orders to Packeta with just one click.
 * Version: 1.2.0
 * Author: Zásilkovna s.r.o.
 * Author URI: https://www.zasilkovna.cz/
 * Text Domain: packetery
 * Domain Path: /languages
 * Requires at least: WordPress 5.3, WooCommerce 4.5
 * Tested up to: WordPress 5.9.2, WooCommerce 6.3.1
 * Requires PHP: 7.2
 *
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

use Packetery\Module\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( php_sapi_name() === 'cli' ) {
	return;
}

$container = require __DIR__ . '/bootstrap.php';
$container->getByType( Plugin::class )->run();
