<?php
/**
 * Class ModuleHelper
 *
 * @package Packetery\Module
 */

declare( strict_types=1 );

namespace Packetery\Module;

use Automattic\WooCommerce\Utilities\OrderUtil;
use Packetery\Nette\Utils\Html;

/**
 * Class ModuleHelper
 *
 * @package Packetery\Module
 */
class ModuleHelper {

	/**
	 * Gets order detail url.
	 *
	 * @param int $orderId Order ID.
	 *
	 * @return string
	 */
	public static function getOrderDetailUrl( int $orderId ): string {
		$queryVars = [];

		if ( self::isHposEnabled() ) {
			$queryVars['page'] = 'wc-orders';
			$queryVars['id']   = $orderId;
			$path              = 'admin.php';
		} else {
			$queryVars['post_type'] = 'shop_order';
			$queryVars['post']      = $orderId;
			$path                   = 'post.php';
		}

		$queryVars['action'] = 'edit';

		return add_query_arg( $queryVars, admin_url( $path ) );
	}

	/**
	 * Gets order grid url.
	 *
	 * @param array $queryVars Query vars.
	 *
	 * @return string
	 */
	public static function getOrderGridUrl( array $queryVars = [] ): string {
		if ( self::isHposEnabled() ) {
			$queryVars['page'] = 'wc-orders';
			$path              = 'admin.php';
		} else {
			$queryVars['post_type'] = 'shop_order';
			$path                   = 'edit.php';
		}

		return add_query_arg( $queryVars, admin_url( $path ) );
	}

	/**
	 * Tells if plugin is active.
	 *
	 * @param string $pluginRelativePath Relative path of plugin bootstrap file.
	 *
	 * @return bool
	 */
	public static function isPluginActive( string $pluginRelativePath ): bool {
		if ( is_multisite() ) {
			$plugins = get_site_option( 'active_sitewide_plugins' );
			if ( isset( $plugins[ $pluginRelativePath ] ) ) {
				return true;
			}
		}

		if ( ! function_exists( 'get_mu_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$muPlugins = get_mu_plugins();
		if ( isset( $muPlugins[ $pluginRelativePath ] ) ) {
			return true;
		}

		return in_array( $pluginRelativePath, (array) get_option( 'active_plugins', [] ), true );
	}

	/**
	 * Introduced as ManageWP Worker plugin hack fix.
	 *
	 * @return void
	 */
	public static function transformGlobalCookies(): void {
		if ( empty( $_COOKIE ) ) {
			return;
		}
		foreach ( $_COOKIE as $key => $value ) {
			// @codingStandardsIgnoreStart
			if ( is_int( $value ) ) {
				$_COOKIE[ $key ] = (string) $value;
			}
			// @codingStandardsIgnoreEnd
		}
	}

	/**
	 * Gets WooCommerce version.
	 *
	 * @return string|null
	 */
	public static function getWooCommerceVersion(): ?string {
		if ( false === file_exists( WP_PLUGIN_DIR . '/woocommerce/woocommerce.php' ) ) {
			return null;
		}

		$version = get_file_data( WP_PLUGIN_DIR . '/woocommerce/woocommerce.php', [ 'Version' => 'Version' ], 'plugin' )['Version'];
		if ( ! $version ) {
			return null;
		}

		return $version;
	}

	/**
	 * Renders string.
	 *
	 * @param string $string String to render.
	 *
	 * @return void
	 */
	public static function renderString( string $string ): void {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $string;
	}

	/**
	 * Gets country from WC order.
	 *
	 * @param \WC_Order $wcOrder WC order.
	 *
	 * @return string May be empty.
	 */
	public static function getWcOrderCountry( \WC_Order $wcOrder ): string {
		$country = $wcOrder->get_shipping_country();
		if ( empty( $country ) ) {
			$country = $wcOrder->get_billing_country();
		}

		return strtolower( $country );
	}

	/**
	 * Tells if High Performance Order Storage is enabled.
	 *
	 * @return bool
	 */
	public static function isHposEnabled(): bool {
		if ( false === class_exists( 'Automattic\\WooCommerce\\Utilities\\OrderUtil' ) ) {
			return false;
		}

		if ( false === method_exists( OrderUtil::class, 'custom_orders_table_usage_is_enabled' ) ) {
			return false;
		}

		return OrderUtil::custom_orders_table_usage_is_enabled();
	}


	/**
	 * Converts all float values within an array to strings.
	 *
	 * @param array $array Array with parameters.
	 *
	 * @return array
	 */
	public static function convertArrayFloatsToStrings( array $array ): array {
		array_walk_recursive(
			$array,
			static function ( &$item ) {
				if ( is_float( $item ) ) {
					$item = (string) $item;
				}
			}
		);

		return $array;
	}

	/**
	 * Creates a named tracking URL for packet.
	 *
	 * @param string $trackingUrl Tracking URL.
	 * @param string $text        Text.
	 * @param string $target      Target attribute.
	 *
	 * @return Html
	 */
	public function createHtmlLink( string $trackingUrl, string $text, string $target = '_blank' ): Html {
		return Html::el( 'a' )
			->href( $trackingUrl )
			->setText( $text )
			->setAttribute( 'target', $target );
	}

	/**
	 * Creates HTML link parts in array.
	 *
	 * @param string      $href Href.
	 * @param string|null $target Target.
	 * @param string|null $class Class.
	 *
	 * @return string[]
	 */
	public function createLinkParts( string $href, string $target = null, string $class = null ): array {
		$link = Html::el( 'a' )->href( $href );

		if ( null !== $target ) {
			$link->target( $target );
		}

		if ( null !== $class ) {
			$link->class( $class );
		}

		return [ $link->startTag(), $link->endTag() ];
	}

}
