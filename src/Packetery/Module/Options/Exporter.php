<?php
/**
 * Class Exporter
 *
 * @package Packetery\Module\Options
 */

declare( strict_types=1 );

namespace Packetery\Module\Options;

use Packetery\Core\Log\Record;
use Packetery\Latte\Engine;
use Packetery\Module\Carrier\CountryListingPage;
use Packetery\Module\Checkout\CurrencySwitcherService;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Log\DbLogger;
use Packetery\Module\ModuleHelper;
use Packetery\Nette\Http;
use Packetery\Tracy\Debugger;

/**
 * Class Exporter
 *
 * @package Packetery\Module\Options
 */
class Exporter {

	public const OPTION_LAST_SETTINGS_EXPORT = 'packetery_last_settings_export';
	public const ACTION_EXPORT_SETTINGS      = 'export-settings';

	/**
	 * @var Http\Request
	 */
	private $httpRequest;

	/**
	 * @var Engine
	 */
	private $latteEngine;

	/**
	 * @var CountryListingPage
	 */
	private $countryListingPage;

	/**
	 * @var OptionsProvider
	 */
	private $optionsProvider;

	/**
	 * @var DbLogger
	 */
	private $dbLogger;

	/**
	 * @var ModuleHelper
	 */
	private $moduleHelper;

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * @var WcAdapter
	 */
	private $wcAdapter;

	public function __construct(
		Http\Request $httpRequest,
		Engine $latteEngine,
		CountryListingPage $countryListingPage,
		OptionsProvider $optionsProvider,
		DbLogger $dbLogger,
		ModuleHelper $moduleHelper,
		WpAdapter $wpAdapter,
		WcAdapter $wcAdapter
	) {
		$this->httpRequest        = $httpRequest;
		$this->latteEngine        = $latteEngine;
		$this->countryListingPage = $countryListingPage;
		$this->optionsProvider    = $optionsProvider;
		$this->dbLogger           = $dbLogger;
		$this->moduleHelper       = $moduleHelper;
		$this->wpAdapter          = $wpAdapter;
		$this->wcAdapter          = $wcAdapter;
	}

	/**
	 * Prepares and outputs export text.
	 */
	public function outputExportTxt(): void {
		global $wpdb;

		if (
			$this->httpRequest->getQuery( 'page' ) !== Page::SLUG ||
			$this->httpRequest->getQuery( 'action' ) !== self::ACTION_EXPORT_SETTINGS
		) {
			return;
		}

		$globalSettings = $this->optionsProvider->getAllOptions();
		if ( isset( $globalSettings[ OptionsProvider::OPTION_NAME_PACKETERY ]['api_password'] ) ) {
			$globalSettings[ OptionsProvider::OPTION_NAME_PACKETERY ]['api_password'] = sprintf(
				'%s...%s (%s)',
				substr( $globalSettings[ OptionsProvider::OPTION_NAME_PACKETERY ]['api_password'], 0, 16 ),
				substr( $globalSettings[ OptionsProvider::OPTION_NAME_PACKETERY ]['api_password'], - 2, 2 ),
				strlen( $globalSettings[ OptionsProvider::OPTION_NAME_PACKETERY ]['api_password'] )
			);
			unset( $globalSettings[ OptionsProvider::OPTION_NAME_PACKETERY ]['api_key'] );
		}
		$globalSettings['woocommerce_allowed_countries']          = get_option( 'woocommerce_allowed_countries' );
		$globalSettings['woocommerce_specific_allowed_countries'] = get_option( 'woocommerce_specific_allowed_countries' );
		$globalSettings['woocommerce_ship_to_countries']          = get_option( 'woocommerce_ship_to_countries' );
		$globalSettings['woocommerce_specific_ship_to_countries'] = get_option( 'woocommerce_specific_ship_to_countries' );

		$shippingTaxClass = $this->wpAdapter->getOption( 'woocommerce_shipping_tax_class' );

		$taxes = [
			'calc_taxes'                  => $this->wpAdapter->getOption( 'woocommerce_calc_taxes' ),
			'prices_include_tax'          => $this->wpAdapter->getOption( 'woocommerce_prices_include_tax' ),
			'tax_based_on'                => $this->wpAdapter->getOption( 'woocommerce_tax_based_on' ),
			'shipping_tax_class'          => $shippingTaxClass,
			'tax_round_at_subtotal'       => $this->wpAdapter->getOption( 'woocommerce_tax_round_at_subtotal' ),
			'tax_classes'                 => $this->wpAdapter->getOption( 'woocommerce_tax_classes' ),
			'tax_display_shop'            => $this->wpAdapter->getOption( 'woocommerce_tax_display_shop' ),
			'tax_display_cart'            => $this->wpAdapter->getOption( 'woocommerce_tax_display_cart' ),
			'tax_total_display'           => $this->wpAdapter->getOption( 'woocommerce_tax_total_display' ),
			'shipping_tax_class_settings' => $this->wcAdapter->taxGetRates( $shippingTaxClass ),
		];

		$activeTheme            = wp_get_theme();
		$themeLatestVersion     = \WC_Admin_Status::get_latest_theme_version( $activeTheme );
		$themeLatestVersionInfo = ( $themeLatestVersion !== $activeTheme->version ? ' (' . $themeLatestVersion . ' available)' : '' );
		$latteParams            = [
			'siteUrl'           => get_option( 'siteurl' ),
			'wpVersion'         => get_bloginfo( 'version' ),
			'wcVersion'         => WC_VERSION,
			'template'          => $activeTheme->name . ' ' . $activeTheme->version . $themeLatestVersionInfo,
			'phpVersion'        => PHP_VERSION,
			'dbServer'          => $wpdb->db_server_info(),
			'soap'              => wc_bool_to_string( extension_loaded( 'soap' ) ),
			'wpDebug'           => wc_bool_to_string( WP_DEBUG ),
			'packetaDebug'      => wc_bool_to_string( Debugger::isEnabled() ),
			'globalSettings'    => $this->formatVariable( $globalSettings ),
			'lastCarrierUpdate' => $this->countryListingPage->getLastUpdate(),
			'carriers'          => $this->formatVariable( $this->countryListingPage->getCarriersForOptionsExport(), 0, true ),
			'zones'             => $this->formatVariable( \WC_Shipping_Zones::get_zones() ),
			'taxes'             => $this->formatVariable( $taxes ),
			'lastFiveDaysLogs'  => $this->formatVariable( $this->remapLogRecords( $this->dbLogger->getForPeriodAsArray( [ [ 'after' => '5 days ago' ] ] ) ) ),
			'generated'         => gmdate( 'Y-m-d H:i:s' ),
			/**
			 * Filter all_plugins filters the full array of plugins.
			 *
			 * @since 3.0.0
			 */
			'plugins'           => $this->getFormattedPlugins( (array) apply_filters( 'all_plugins', get_plugins() ) ),
			'muPlugins'         => $this->getFormattedPlugins( get_mu_plugins() ),
			'currencySwitchers' => $this->formatVariable( CurrencySwitcherService::$supportedCurrencySwitchers ),
		];
		update_option( self::OPTION_LAST_SETTINGS_EXPORT, gmdate( DATE_ATOM ) );

		$txtContents = $this->latteEngine->renderToString( PACKETERY_PLUGIN_DIR . '/template/options/export.latte', $latteParams );
		header( 'Content-Type: text/plain' );
		header( 'Content-Transfer-Encoding: Binary' );
		header( 'Content-Length: ' . strlen( $txtContents ) );
		header( 'Content-Disposition: attachment; filename="packeta_options_export_' . gmdate( 'Y-m-d-H-i-s' ) . '.txt"' );
		// @codingStandardsIgnoreStart
		echo $txtContents;
		// @codingStandardsIgnoreEnd
		exit;
	}

	/**
	 * Format plugins.
	 *
	 * @param array<string, array<string, string>> $plugins Plugins.
	 *
	 * @return string
	 */
	private function getFormattedPlugins( array $plugins ): string {
		$result = [];

		foreach ( $plugins as $relativePath => $plugin ) {
			$item = [
				'Active' => wc_bool_to_string( $this->moduleHelper->isPluginActive( $relativePath ) ),
			];

			$options = [ 'Name', 'PluginURI', 'Version', 'WC tested up to', 'WC requires at least', 'AuthorName', 'RequiresPHP' ];
			foreach ( $options as $option ) {
				$item[ $option ] = $plugin[ $option ] ?? '';
			}

			$result[] = array_filter( $item );
		}

		return $this->formatVariable( $result );
	}

	/**
	 * @param \Generator<Record>|array{} $logs
	 * @return array<array<string, array<string, mixed>|string>>
	 */
	private function remapLogRecords( iterable $logs ): array {
		$result = [];

		foreach ( $logs as $log ) {
			$result[] = [
				'date'   => $log->date->format( wc_date_format() . ' ' . wc_time_format() ),
				'action' => $log->action,
				'status' => $log->status,
				'title'  => $log->title,
				'params' => $log->params,
			];
		}

		return $result;
	}

	/**
	 * Formats variable for text export.
	 *
	 * @param mixed $variable Variable to export.
	 * @param int   $level Nesting level.
	 * @param bool  $addSeparator Whether to use separator for top level items.
	 *
	 * @return string
	 */
	private function formatVariable( $variable, int $level = 0, bool $addSeparator = false ): string {
		$output = '';
		if ( is_array( $variable ) ) {
			foreach ( $variable as $key => $value ) {
				if ( $level === 0 && $addSeparator ) {
					$output .= '----------------------' . PHP_EOL;
				}
				$output .= str_repeat( '    ', $level );
				$output .= $key . ':';
				if ( is_array( $value ) ) {
					$output .= PHP_EOL;
				} else {
					$output .= ' ';
				}
				$output .= $this->formatVariable( $value, $level + 1 );
			}
		} elseif ( is_bool( $variable ) ) {
			// @codingStandardsIgnoreStart
			$output .= var_export( $variable, true ) . PHP_EOL;
			// @codingStandardsIgnoreEnd
		} elseif ( $variable instanceof \stdClass ) {
			$output .= PHP_EOL . $this->formatVariable( (array) $variable, $level );
		} elseif ( $variable instanceof \WC_Shipping_Method ) {
			$methodInfo = [
				'id'           => $variable->id,
				// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
				'method_title' => $variable->method_title,
				'enabled'      => $variable->enabled,
			];
			$output    .= PHP_EOL . $this->formatVariable( $methodInfo, $level );
		} elseif ( is_object( $variable ) ) {
			$output .= gettype( $variable ) . ' ' . get_class( $variable ) . PHP_EOL;
		} else {
			$output .= $variable . PHP_EOL;
		}

		return $output;
	}
}
