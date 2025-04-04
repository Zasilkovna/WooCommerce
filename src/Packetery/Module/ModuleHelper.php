<?php
/**
 * Class ModuleHelper
 *
 * @package Packetery\Module
 */

declare( strict_types=1 );

namespace Packetery\Module;

use Automattic\WooCommerce\Utilities\OrderUtil;
use DateTimeImmutable;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Nette\Utils\Html;
use WC_DateTime;

/**
 * Class ModuleHelper
 *
 * @package Packetery\Module
 */
class ModuleHelper {

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	public function __construct( WpAdapter $wpAdapter ) {
		$this->wpAdapter = $wpAdapter;
	}

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
	public function isPluginActive( string $pluginRelativePath ): bool {
		if ( $this->wpAdapter->isMultisite() ) {
			$plugins = $this->wpAdapter->getSiteOption( 'active_sitewide_plugins' );
			if ( isset( $plugins[ $pluginRelativePath ] ) ) {
				return true;
			}
		}

		if ( ! function_exists( 'get_mu_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$muPlugins = $this->wpAdapter->getMuPlugins();
		if ( isset( $muPlugins[ $pluginRelativePath ] ) ) {
			return true;
		}

		return in_array( $pluginRelativePath, (array) $this->wpAdapter->getOption( 'active_plugins', [] ), true );
	}

	/**
	 * Gets WooCommerce version.
	 *
	 * @return string|null
	 */
	public static function getWooCommerceVersion(): ?string {
		if ( file_exists( WP_PLUGIN_DIR . '/woocommerce/woocommerce.php' ) === false ) {
			return null;
		}

		$version  = '';
		$fileData = get_file_data( WP_PLUGIN_DIR . '/woocommerce/woocommerce.php', [ 'Version' => 'Version' ], 'plugin' );

		if ( isset( $fileData['Version'] ) ) {
			$version = $fileData['Version'];
		}

		if ( $version === '' ) {
			return null;
		}

		return $version;
	}

	/**
	 * Renders string.
	 *
	 * @param string $inputString String to render.
	 *
	 * @return void
	 */
	public static function renderString( string $inputString ): void {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $inputString;
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
		if ( $country === null || $country === '' ) {
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
		if ( class_exists( 'Automattic\\WooCommerce\\Utilities\\OrderUtil' ) === false ) {
			return false;
		}
		// @phpstan-ignore-next-line (This method was probably added in WC version 6.9.0, backward compatibility)
		if ( method_exists( OrderUtil::class, 'custom_orders_table_usage_is_enabled' ) === false ) {
			return false;
		}

		return OrderUtil::custom_orders_table_usage_is_enabled();
	}

	/**
	 * Converts all float values within an array to strings.
	 *
	 * @param array $inputArray Array with parameters.
	 *
	 * @return array
	 */
	public static function convertArrayFloatsToStrings( array $inputArray ): array {
		array_walk_recursive(
			$inputArray,
			static function ( &$item ) {
				if ( is_float( $item ) ) {
					$item = (string) $item;
				}
			}
		);

		return $inputArray;
	}

	/**
	 * Returns null for value lesser than 1.
	 *
	 * @param int $number Value (mm).
	 *
	 * @return float|null
	 */
	public static function convertToCentimeters( int $number ): ?float {
		return $number < 1 ? null : ( $number * 0.1 );
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
	 * Returns null for value lesser than 0.1.
	 *
	 * @param float $number Value (cm).
	 *
	 * @return float|null
	 */
	public static function convertToMillimeters( float $number ): ?float {
		return $number < 0.1 ? null : ( $number * 10 );
	}

	/**
	 * Creates HTML link parts in array.
	 *
	 * @param string      $href Href.
	 * @param string|null $target Target.
	 * @param string|null $className Class.
	 *
	 * @return string[]
	 */
	public function createLinkParts( string $href, ?string $target = null, ?string $className = null ): array {
		$link = Html::el( 'a' )->href( $href );

		if ( $target !== null ) {
			$link->target( $target );
		}

		if ( $className !== null ) {
			$link->class( $className );
		}

		return [ $link->startTag(), $link->endTag() ];
	}

	/**
	 * Creates translated Date
	 *
	 * @param DateTimeImmutable|null $date   Datetime.
	 *
	 * @return string|null
	 */
	public function getTranslatedStringFromDateTime( ?DateTimeImmutable $date ): ?string {
		if ( $date !== null ) {
			$wcDateTime = new WC_DateTime();
			$wcDateTime->setTimestamp( $date->getTimestamp() );

			return $wcDateTime->date_i18n(
				/**
				 * Applies woocommerce_admin_order_date_format filters.
				 *
				 * @since 1.8.3
				 */
				apply_filters( 'woocommerce_admin_order_date_format', __( 'M j, Y', 'woocommerce' ) ) //phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
			);
		}

		return null;
	}

	public function isWooCommercePluginActive(): bool {
		return $this->isPluginActive( 'woocommerce/woocommerce.php' );
	}

	public static function getPluginMainFilePath(): string {
		return PACKETERY_PLUGIN_DIR . '/packeta.php';
	}

	/**
	 * Gets current locale.
	 */
	public function getLocale(): string {
		/**
		 * Applies plugin_locale filters.
		 *
		 * @since 1.0.0
		 */
		return (string) $this->wpAdapter->applyFilters(
			'plugin_locale',
			( $this->wpAdapter->isAdmin() ? $this->wpAdapter->getUserLocale() : $this->wpAdapter->getLocale() ),
			Plugin::DOMAIN
		);
	}

	public function isCzechLocale(): bool {
		return $this->getLocale() === 'cs_CZ';
	}
}
