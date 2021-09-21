<?php
/**
 * Packeta carrier downloader
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Carrier;

use Packetery\Options\Provider;
use PacketeryGuzzleHttp\Client;
use PacketeryGuzzleHttp\Exception\GuzzleException;
use PacketeryGuzzleHttp\PacketeryPsr7\Response;

/**
 * Class Downloader
 *
 * @package Packetery
 */
class Downloader {
	private const API_URL                   = 'https://www.zasilkovna.cz/api/v4/%s/branch.json?address-delivery';
	public const OPTION_LAST_CARRIER_UPDATE = 'packetery_last_carrier_update';

	/**
	 * Guzzle client.
	 *
	 * @var Client Guzzle client.
	 */
	private $client;

	/**
	 * Carrier updater.
	 *
	 * @var Updater Carrier updater.
	 */
	private $carrier_updater;

	/**
	 * Options provider.
	 *
	 * @var Provider Options provider.
	 */
	private $options_provider;

	/**
	 * Downloader constructor.
	 *
	 * @param Client   $guzzle_client Guzzle client.
	 * @param Updater  $carrier_updater Carrier updater.
	 * @param Provider $options_provider Options provider.
	 */
	public function __construct( Client $guzzle_client, Updater $carrier_updater, Provider $options_provider ) {
		$this->client           = $guzzle_client;
		$this->carrier_updater  = $carrier_updater;
		$this->options_provider = $options_provider;
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
					__( 'Carrier download failed: %failReason Please try again later.', 'packetery' ),
					array( '%failReason' => $e->getMessage() )
				),
				'error',
			];
		}
		if ( ! $carriers ) {
			return [
				strtr(
				// translators: keep %failReason placeholder intact.
					__( 'Carrier download failed: %failReason Please try again later.', 'packetery' ),
					array( '%failReason' => __( 'Failed to get the list.', 'packetery' ) )
				),
				'error',
			];
		}
		$validation_result = $this->carrier_updater->validate_carrier_data( $carriers );
		if ( ! $validation_result ) {
			return [
				strtr(
				// translators: keep %failReason placeholder intact.
					__( 'Carrier download failed: %failReason Please try again later.', 'packetery' ),
					array( '%failReason' => __( 'Invalid API response.', 'packetery' ) )
				),
				'error',
			];
		}
		$this->carrier_updater->save( $carriers );
		update_option( self::OPTION_LAST_CARRIER_UPDATE, gmdate( 'Y-m-d H:i:s' ) );

		return [
			__( 'Carriers were updated.', 'packetery' ),
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
	 * @throws DownloadException DownloadException.
	 */
	private function fetch_as_array(): ?array {
		$json = $this->download_json();

		return $this->get_from_json( $json );
	}

	/**
	 * Downloads carriers in JSON.
	 *
	 * @return string
	 * @throws DownloadException DownloadException.
	 */
	private function download_json(): string {
		$url = sprintf( self::API_URL, $this->options_provider->get_api_key() );
		try {
			/**
			 * Guzzle response.
			 *
			 * @var Response $result Guzzle response.
			 */
			$result = $this->client->get( $url );
		} catch ( GuzzleException $exception ) {
			throw new DownloadException( $exception->getMessage() );
		}

		return $result->getBody()->getContents();
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
