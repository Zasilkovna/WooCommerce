<?php
/**
 * Class Exporter
 *
 * @package Packetery\Module\Options
 */

declare( strict_types=1 );

namespace Packetery\Module\Options;

use Packetery\Module\Carrier\CountryListingPage;
use Packetery\Module\Log\PostLogger;
use PacketeryLatte\Engine;
use PacketeryNette\Http;

/**
 * Class Exporter
 *
 * @package Packetery\Module\Options
 */
class Exporter {

	public const OPTION_LAST_SETTINGS_EXPORT = 'packetery_last_settings_export';
	public const ACTION_EXPORT_SETTINGS      = 'export-settings';

	/**
	 * Http request.
	 *
	 * @var Http\Request
	 */
	private $httpRequest;

	/**
	 * Latte engine.
	 *
	 * @var Engine
	 */
	private $latteEngine;

	/**
	 * Country listing page.
	 *
	 * @var CountryListingPage
	 */
	private $countryListingPage;

	/**
	 * Options provider.
	 *
	 * @var Provider
	 */
	private $optionsProvider;

	/**
	 * Post logger.
	 *
	 * @var PostLogger
	 */
	private $postLogger;

	/**
	 * Exporter constructor.
	 *
	 * @param Http\Request       $httpRequest Http request.
	 * @param Engine             $latteEngine Latte engine.
	 * @param CountryListingPage $countryListingPage Country listing page.
	 * @param Provider           $optionsProvider Options provider.
	 * @param PostLogger         $postLogger Post logger.
	 */
	public function __construct(
		Http\Request $httpRequest,
		Engine $latteEngine,
		CountryListingPage $countryListingPage,
		Provider $optionsProvider,
		PostLogger $postLogger
	) {
		$this->httpRequest        = $httpRequest;
		$this->latteEngine        = $latteEngine;
		$this->countryListingPage = $countryListingPage;
		$this->optionsProvider    = $optionsProvider;
		$this->postLogger         = $postLogger;
	}

	/**
	 * Prepares and outputs export text.
	 */
	public function outputExportTxt(): void {
		if (
			$this->httpRequest->getQuery( 'page' ) !== Page::SLUG ||
			$this->httpRequest->getQuery( 'action' ) !== self::ACTION_EXPORT_SETTINGS
		) {
			return;
		}

		$globalSettings = $this->optionsProvider->data_to_array();
		if ( ! empty( $globalSettings['api_password'] ) ) {
			$globalSettings['api_password'] = sprintf(
				'%s...%s (%s)',
				substr( $globalSettings['api_password'], 0, 16 ),
				substr( $globalSettings['api_password'], - 2, 2 ),
				strlen( $globalSettings['api_password'] )
			);
			unset( $globalSettings['api_key'] );
		}
		$globalSettings['woocommerce_allowed_countries']          = get_option( 'woocommerce_allowed_countries' );
		$globalSettings['woocommerce_specific_allowed_countries'] = get_option( 'woocommerce_specific_allowed_countries' );
		$globalSettings['woocommerce_ship_to_countries']          = get_option( 'woocommerce_ship_to_countries' );
		$globalSettings['woocommerce_specific_ship_to_countries'] = get_option( 'woocommerce_specific_ship_to_countries' );

		$activeTheme            = wp_get_theme();
		$themeLatestVersion     = \WC_Admin_Status::get_latest_theme_version( $activeTheme );
		$themeLatestVersionInfo = ( $themeLatestVersion !== $activeTheme->version ? ' (' . $themeLatestVersion . ' available)' : '' );
		$latteParams            = [
			'wpVersion'         => get_bloginfo( 'version' ),
			'wcVersion'         => WC_VERSION,
			'template'          => $activeTheme->name . ' ' . $activeTheme->version . $themeLatestVersionInfo,
			'phpVersion'        => PHP_VERSION,
			// @codingStandardsIgnoreStart
			'soap'              => var_export( extension_loaded( 'soap' ), true ),
			'wpDebug'           => var_export( WP_DEBUG, true ),
			'packetaDebug'      => var_export( PACKETERY_DEBUG, true ),
			// @codingStandardsIgnoreStart
			'globalSettings'    => $this->formatVariable( $globalSettings ),
			'lastCarrierUpdate' => $this->countryListingPage->getLastUpdate(),
			'carriers'          => $this->formatVariable( $this->countryListingPage->getCarriersForOptionsExport(), 0, true ),
			'zones'             => $this->formatVariable( \WC_Shipping_Zones::get_zones() ),
			'lastFiveDaysLogs'  => $this->formatVariable( $this->postLogger->getForPeriodAsArray( [ [ 'after' => '5 days ago' ] ] ) ),
			'generated'         => gmdate( 'Y-m-d H:i:s' ),
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
	 * Formats variable for text export.
	 *
	 * @param mixed $variable Variable to export.
	 * @param int   $level Nesting level.
	 * @param bool  $addSeparator Whether to use separator for top level items.
	 *
	 * @return string
	 */
	private function formatVariable( $variable, int $level = 0, $addSeparator = false ): string {
		$output = '';
		if ( is_array( $variable ) ) {
			foreach ( $variable as $key => $value ) {
				if ( 0 === $level && $addSeparator ) {
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
