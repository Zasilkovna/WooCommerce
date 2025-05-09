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
 * Version: 2.0.4
 * Author: Zásilkovna s.r.o.
 * Author URI: https://www.zasilkovna.cz/
 * Text Domain: packeta
 * Domain Path: /languages
 * Requires at least: 5.5
 * Requires PHP: 7.2
 * Requires Plugins: woocommerce
 *
 * Tested up to: 6.7.2
 * WC requires at least: 5.1
 * WC tested up to: 9.7.1
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

use Packetery\Module\Plugin;
use Packetery\Nette\DI\Container;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	$_SERVER['REQUEST_URI'] = '/wp-cli-fake-url';
	$_SERVER['SCRIPT_NAME'] = '/wp-cli-fake-script.php';
}


/** @var Container $container */
$container = require __DIR__ . '/bootstrap.php';
/** @var Plugin $packetaPlugin */
$packetaPlugin = $container->getByType( Plugin::class );
$packetaPlugin->run();
