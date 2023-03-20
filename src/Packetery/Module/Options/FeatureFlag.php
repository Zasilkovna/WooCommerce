<?php
/**
 * Class FeatureFlag
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Options;

use DateTimeImmutable;
use Exception;
use Packetery\Core\Helper;
use Packetery\Module\Exception\DownloadException;
use PacketeryGuzzleHttp\Client;
use PacketeryGuzzleHttp\Exception\GuzzleException;
use PacketeryGuzzleHttp\PacketeryPsr7\Response;

/**
 * Class FeatureFlag
 *
 * @package Packetery
 */
class FeatureFlag {

	private const ENDPOINT_URL       = 'https://pes-features-test.packeta-com.codenow.com/v1/wp';
	private const VALID_FOR_HOURS    = 4;
	private const FLAGS_OPTION_ID    = 'packeta_feature_flags';
	private const FLAG_SPLIT_ACTIVE  = 'splitActive';
	private const FLAG_LAST_DOWNLOAD = 'lastDownload';

	/**
	 * Guzzle client.
	 *
	 * @var Client Guzzle client.
	 */
	private $client;

	/**
	 * Downloader constructor.
	 *
	 * @param Client $guzzleClient Guzzle client.
	 */
	public function __construct( Client $guzzleClient ) {
		$this->client = $guzzleClient;
	}

	/**
	 * Downloads flags.
	 *
	 * @return array
	 * @throws DownloadException Download exception.
	 * @throws Exception From DateTimeImmutable.
	 */
	private function fetchFlags(): array {
		/**
		 * Guzzle response.
		 *
		 * @var Response $response Guzzle response.
		 */
		try {
			$response = $this->client->get( self::ENDPOINT_URL );
		} catch ( GuzzleException $exception ) {
			throw new DownloadException( $exception->getMessage() );
		}
		$responseJson    = $response->getBody()->getContents();
		$responseDecoded = json_decode( $responseJson, true );

		$flags = [
			self::FLAG_SPLIT_ACTIVE => (bool) $responseDecoded['features']['split'],
		];

		$lastDownload                      = new DateTimeImmutable( 'now', wp_timezone() );
		$flags[ self::FLAG_LAST_DOWNLOAD ] = $lastDownload->format( Helper::MYSQL_DATETIME_FORMAT );
		update_option( self::FLAGS_OPTION_ID, $flags );

		return $flags;
	}

	/**
	 * Gets or downloads flags.
	 *
	 * @return array
	 * @throws DownloadException Download exception.
	 * @throws Exception From DateTimeImmutable.
	 */
	private function getFlags(): array {
		static $flags;

		if ( ! isset( $flags ) ) {
			$flags = get_option( self::FLAGS_OPTION_ID );
		}

		if ( false === $flags ) {
			return $this->fetchFlags();
		}

		$now        = new DateTimeImmutable( 'now', wp_timezone() );
		$lastUpdate = DateTimeImmutable::createFromFormat(
			Helper::MYSQL_DATETIME_FORMAT,
			$flags[ self::FLAG_LAST_DOWNLOAD ],
			wp_timezone()
		);
		$ageHours   = ( ( $now->getTimestamp() - $lastUpdate->getTimestamp() ) / HOUR_IN_SECONDS );
		if ( $ageHours >= self::VALID_FOR_HOURS ) {
			$flags = $this->fetchFlags();
		}

		return $flags;
	}

	/**
	 * Tells if split is active.
	 *
	 * @return bool
	 * @throws DownloadException Download exception.
	 */
	public function isSplitActive(): bool {
		$flags = $this->getFlags();
		if ( isset( $flags[ self::FLAG_SPLIT_ACTIVE ] ) ) {
			return (bool) $flags[ self::FLAG_SPLIT_ACTIVE ];
		}

		return false;
	}

}
