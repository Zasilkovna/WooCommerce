<?php
/**
 * Packeta carrier downloader
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Carrier;

use Packetery\Core\Api\WebRequestException;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\WebRequestClient;

/**
 * Class Downloader
 *
 * @package Packetery
 */
class Downloader {
	private const API_URL                   = 'https://pickup-point.api.packeta.com/v5/%s/carrier/json?lang=%s';
	public const OPTION_LAST_CARRIER_UPDATE = 'packetery_last_carrier_update';

	/**
	 * Carrier updater.
	 *
	 * @var Updater Carrier updater.
	 */
	private $carrierUpdater;

	/**
	 * Options provider.
	 *
	 * @var OptionsProvider Options provider.
	 */
	private $optionsProvider;

	/**
	 * HTTP client.
	 *
	 * @var WebRequestClient
	 */
	private $webRequestClient;

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * Downloader constructor.
	 *
	 * @param Updater          $carrierUpdater  Carrier updater.
	 * @param OptionsProvider  $optionsProvider  Options provider.
	 * @param WebRequestClient $webRequestClient HTTP client.
	 * @param WpAdapter        $wpAdapter        WP adapter.
	 */
	public function __construct(
		Updater $carrierUpdater,
		OptionsProvider $optionsProvider,
		WebRequestClient $webRequestClient,
		WpAdapter $wpAdapter
	) {
		$this->carrierUpdater   = $carrierUpdater;
		$this->optionsProvider  = $optionsProvider;
		$this->webRequestClient = $webRequestClient;
		$this->wpAdapter        = $wpAdapter;
	}

	/**
	 * Runs update and returns result.
	 *
	 * @return string[]
	 */
	public function run(): array {
		try {
			$carriers = $this->fetch_as_array( substr( $this->wpAdapter->getLocale(), 0, 2 ) );
		} catch ( \Exception $e ) {
			return [
				strtr(
				// translators: keep %failReason placeholder intact.
					__( 'Carrier download failed: %failReason Please try again later.', 'packeta' ),
					array( '%failReason' => $e->getMessage() )
				),
				'error',
			];
		}
		if ( $carriers === null || count( $carriers ) === 0 ) {
			// translators: keep %failReason placeholder intact.
			$translatedMessage = __( 'Carrier download failed: %failReason Please try again later.', 'packeta' );

			return [
				strtr(
					$translatedMessage,
					array( '%failReason' => __( 'Failed to get the list.', 'packeta' ) )
				),
				'error',
			];
		}
		$validationResult = $this->carrierUpdater->validate_carrier_data( $carriers );
		if ( ! $validationResult ) {
			// translators: keep %failReason placeholder intact.
			$translatedMessage = __( 'Carrier download failed: %failReason Please try again later.', 'packeta' );

			return [
				strtr(
					$translatedMessage,
					array( '%failReason' => __( 'Invalid API response.', 'packeta' ) )
				),
				'error',
			];
		}
		$this->carrierUpdater->save( $carriers );
		update_option( self::OPTION_LAST_CARRIER_UPDATE, gmdate( DATE_ATOM ) );

		return [
			__( 'Carriers were updated.', 'packeta' ),
			'success',
		];
	}

	/**
	 * Cron job. No authorization needed - job is registered internally.
	 */
	public function runAndRender(): void {
		[ $message, $class ] = $this->run();
		echo esc_html( $message );
	}

	/**
	 * Downloads carriers and returns in array.
	 *
	 * @param string $language Two-letter code.
	 *
	 * @return array<int, array<string, string>>|null
	 * @throws WebRequestException DownloadException.
	 */
	public function fetch_as_array( string $language ): ?array {
		$json = $this->download_json( $language );

		return $this->get_from_json( $json );
	}

	/**
	 * Downloads carriers in JSON.
	 *
	 * @param string $language Two-letter code.
	 *
	 * @return string
	 * @throws WebRequestException DownloadException.
	 */
	private function download_json( string $language ): string {
		return $this->webRequestClient->get(
			sprintf(
				self::API_URL,
				$this->optionsProvider->get_api_key(),
				$language
			)
		);
	}

	/**
	 * Converts JSON to array.
	 *
	 * @param string $json JSON.
	 *
	 * @return array<int, array<string, string>>|null
	 */
	private function get_from_json( string $json ): ?array {
		$carriersData = json_decode( $json, true );

		return ( $carriersData ?? null );
	}
}
