<?php
/**
 * Plugin implementing Packeta shipping methods.
 *
 * @package   Packeta
 *
 * @wordpress-plugin
 *
 * Plugin Name: Packeta
 * Description: This is the official plugin, that allows you to choose pickup points of Packeta and its external carriers in all of Europe, or utilize address delivery to 25 countries in the European Union, straight from the cart in your e-shop. Furthermore, you can also submit all your orders to Packeta with just one click.
 * Version: 1.8.4
 * Author: Zásilkovna s.r.o.
 * Author URI: https://www.zasilkovna.cz/
 * Text Domain: packeta
 * Domain Path: /languages
 * Requires at least: 5.5
 * Requires PHP: 7.2
 *
 * Tested up to: 6.6.2
 * WC requires at least: 4.5
 * WC tested up to: 9.3.3
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

use Packetery\Module\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( PHP_SAPI === 'cli' ) {
	return;
}

$container = require __DIR__ . '/bootstrap.php';
$container->getByType( Plugin::class )->run();
