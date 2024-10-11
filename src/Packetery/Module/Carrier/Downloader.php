<?php
/**
 * Packeta carrier downloader
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Carrier;

use Packetery\Core\Api\WebRequestException;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\WebRequestClient;

/**
 * Class Downloader
 *
 * @package Packetery
 */
class Downloader {
	private const API_URL                   = 'https://www.zasilkovna.cz/api/v4/%s/branch.json?address-delivery';
	public const OPTION_LAST_CARRIER_UPDATE = 'packetery_last_carrier_update';

	/**
	 * Carrier updater.
	 *
	 * @var Updater Carrier updater.
	 */
	private $carrier_updater;

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
	 * Downloader constructor.
	 *
	 * @param Updater          $carrier_updater  Carrier updater.
	 * @param OptionsProvider  $optionsProvider  Options provider.
	 * @param WebRequestClient $webRequestClient HTTP client.
	 */
	public function __construct( Updater $carrier_updater, OptionsProvider $optionsProvider, WebRequestClient $webRequestClient ) {
		$this->carrier_updater  = $carrier_updater;
		$this->optionsProvider  = $optionsProvider;
		$this->webRequestClient = $webRequestClient;
	}

	/**
	 * Runs update and returns result.
	 *
	 * @return array
	 */
	public function run(): array {
		try {
			$carriers = $this->fetch_as_array();
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
		if ( ! $carriers ) {
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
		$validation_result = $this->carrier_updater->validate_carrier_data( $carriers );
		if ( ! $validation_result ) {
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
		$this->carrier_updater->save( $carriers );
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
	 * @return array|null
	 * @throws WebRequestException DownloadException.
	 */
	private function fetch_as_array(): ?array {
		$json = $this->download_json();

		return $this->get_from_json( $json );
	}

	/**
	 * Downloads carriers in JSON.
	 *
	 * @return string
	 * @throws WebRequestException DownloadException.
	 */
	private function download_json(): string {
		return $this->webRequestClient->get( sprintf( self::API_URL, $this->optionsProvider->get_api_key() ) );
	}

	/**
	 * Converts JSON to array.
	 *
	 * @param string $json JSON.
	 *
	 * @return array|null
	 */
	private function get_from_json( string $json ): ?array {
		$carriers_data = json_decode( $json, true );

		return ( $carriers_data['carriers'] ?? null );
	}
}
