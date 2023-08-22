<?php
/**
 * Class FeatureFlagManager
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Options;

use DateTimeImmutable;
use Exception;
use Packetery\GuzzleHttp\Exception\ConnectException;
use Packetery\Core\Helper;
use Packetery\Module\Plugin;
use Packetery\GuzzleHttp\Client;
use Packetery\GuzzleHttp\Exception\GuzzleException;
use Packetery\GuzzleHttp\Psr7\Response;
use Packetery\Latte\Engine;

/**
 * Class FeatureFlagManager
 *
 * @package Packetery
 */
class FeatureFlagManager {

	private const ENDPOINT_URL                  = 'https://pes-features-prod-pes.prod.packeta-com.codenow.com/v1/wp';
	private const VALID_FOR_HOURS               = 4;
	private const FLAGS_OPTION_ID               = 'packeta_feature_flags';
	private const TRANSIENT_SHOW_SPLIT_MESSAGE  = 'packeta_show_split_message';
	public const ACTION_HIDE_SPLIT_MESSAGE      = 'dismiss_split_message';
	private const DISABLED_DUE_ERRORS_OPTION_ID = 'packeta_feature_flags_disabled_due_errors';
	private const ERROR_COUNTER_OPTION_ID       = 'packeta_feature_flags_error_counter';

	private const FLAG_LAST_DOWNLOAD = 'lastDownload';
	private const FLAG_SPLIT_ACTIVE  = 'splitActive';

	/**
	 * Guzzle client.
	 *
	 * @var Client Guzzle client.
	 */
	private $client;

	/**
	 * Latte engine.
	 *
	 * @var Engine
	 */
	private $latteEngine;

	/**
	 * Options provider.
	 *
	 * @var Provider
	 */
	private $optionsProvider;

	/**
	 * Downloader constructor.
	 *
	 * @param Client   $guzzleClient Guzzle client.
	 * @param Engine   $latteEngine Latte engine.
	 * @param Provider $optionsProvider Options provider.
	 */
	public function __construct( Client $guzzleClient, Engine $latteEngine, Provider $optionsProvider ) {
		$this->client          = $guzzleClient;
		$this->latteEngine     = $latteEngine;
		$this->optionsProvider = $optionsProvider;
	}

	/**
	 * Downloads flags.
	 *
	 * @return array
	 * @throws Exception From DateTimeImmutable.
	 */
	private function fetchFlags(): array {
		/**
		 * Guzzle response.
		 *
		 * @var Response $response Guzzle response.
		 */
		try {
			$response = $this->client->get(
				self::ENDPOINT_URL,
				[
					'query'   => [
						'api_key' => $this->optionsProvider->get_api_key(),
					],
					'timeout' => 20,
				]
			);
		} catch ( ConnectException $exception ) {
			$logger = new \WC_Logger();
			$logger->warning( 'Packeta Feature flag API download error: ' . $exception->getMessage() );
			$errorCount = get_option( self::ERROR_COUNTER_OPTION_ID, 0 );
			update_option( self::ERROR_COUNTER_OPTION_ID, $errorCount + 1 );
			if ( $errorCount > 5 ) {
				update_option( self::DISABLED_DUE_ERRORS_OPTION_ID, true );
				$logger->warning( 'Packeta Feature flag API download was disabled due to permanent connection errors.' );
			}

			return [];
		} catch ( GuzzleException $exception ) {
			// We need to not block the presentation. TODO: solve logging.
			return [];
		}

		$responseJson    = $response->getBody()->getContents();
		$responseDecoded = json_decode( $responseJson, true );

		$lastDownload = new DateTimeImmutable( 'now', new \DateTimeZone( 'UTC' ) );
		$flags        = [
			self::FLAG_SPLIT_ACTIVE  => (bool) $responseDecoded['features']['split'],
			self::FLAG_LAST_DOWNLOAD => $lastDownload->format( Helper::MYSQL_DATETIME_FORMAT ),
		];

		update_option( self::FLAGS_OPTION_ID, $flags );
		update_option( self::ERROR_COUNTER_OPTION_ID, 0 );

		return $flags;
	}

	/**
	 * Gets or downloads flags.
	 *
	 * @return array
	 * @throws Exception From DateTimeImmutable.
	 */
	private function getFlags(): array {
		static $flags;

		if ( ! isset( $flags ) ) {
			$flags = get_option( self::FLAGS_OPTION_ID );
		}

		if ( true === get_option( self::DISABLED_DUE_ERRORS_OPTION_ID ) ) {
			return $flags ? $flags : [];
		}

		$hasApiKey = ( null !== $this->optionsProvider->get_api_key() );
		if ( false === $flags ) {
			if ( ! $hasApiKey ) {
				$flags = [];

				return $flags;
			}

			$flags = $this->fetchFlags();

			return $flags;
		}

		if ( $hasApiKey && isset( $flags[ self::FLAG_LAST_DOWNLOAD ] ) ) {
			$now        = new DateTimeImmutable( 'now', new \DateTimeZone( 'UTC' ) );
			$lastUpdate = DateTimeImmutable::createFromFormat(
				Helper::MYSQL_DATETIME_FORMAT,
				$flags[ self::FLAG_LAST_DOWNLOAD ],
				new \DateTimeZone( 'UTC' )
			);
			$ageHours   = ( ( $now->getTimestamp() - $lastUpdate->getTimestamp() ) / HOUR_IN_SECONDS );
			if ( $ageHours >= self::VALID_FOR_HOURS ) {
				$oldFlags = $flags;
				$flags    = $this->fetchFlags();
			}

			if (
				isset( $oldFlags, $flags[ self::FLAG_SPLIT_ACTIVE ] ) &&
				false === $oldFlags[ self::FLAG_SPLIT_ACTIVE ] &&
				true === $flags[ self::FLAG_SPLIT_ACTIVE ]
			) {
				set_transient( self::TRANSIENT_SHOW_SPLIT_MESSAGE, 'yes' );
			}
		}

		return $flags;
	}

	/**
	 * Tells if split is active.
	 *
	 * @return bool
	 * @throws Exception From DateTimeImmutable.
	 */
	public function isSplitActive(): bool {
		$flags = $this->getFlags();
		if ( isset( $flags[ self::FLAG_SPLIT_ACTIVE ] ) ) {
			return (bool) $flags[ self::FLAG_SPLIT_ACTIVE ];
		}

		return false;
	}

	/**
	 * Dismiss split notice.
	 *
	 * @return void
	 */
	public function dismissSplitActivationNotice(): void {
		delete_transient( self::TRANSIENT_SHOW_SPLIT_MESSAGE );
	}

	/**
	 * Determines whether to display split notice.
	 *
	 * @return bool
	 */
	public function hasSplitActivationNotice(): bool {
		return ( 'yes' === get_transient( self::TRANSIENT_SHOW_SPLIT_MESSAGE ) );
	}

	/**
	 * Print split activation notice.
	 *
	 * @return void
	 */
	public function renderSplitActivationNotice(): void {
		$dismissUrl = add_query_arg( [ Plugin::PARAM_PACKETERY_ACTION => self::ACTION_HIDE_SPLIT_MESSAGE ] );
		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/admin-notice.latte',
			[
				'message' => [
					'type'    => 'warning',
					'escape'  => false,
					'message' => sprintf(
					// translators: 1: documentation link start 2: link end 3: dismiss link start 4: link end.
						__(
							'We have just enabled new options for setting Packeta pickup points. You can now choose a different price for Z-Box and pickup points in the carrier settings. More information can be found in %1$sthe plugin documentation%2$s. %3$sDismiss this message%4$s',
							'packeta'
						),
						...Plugin::createLinkParts( 'https://github.com/Zasilkovna/WooCommerce/wiki', '_blank' ),
						...Plugin::createLinkParts( $dismissUrl, null, 'button button-primary' )
					),
				],
			]
		);
	}

}
