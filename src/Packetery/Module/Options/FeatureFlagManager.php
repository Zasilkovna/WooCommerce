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
use Packetery\Core;
use Packetery\Latte\Engine;
use Packetery\Module;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Plugin;

/**
 * Class FeatureFlagManager
 *
 * @package Packetery
 */
class FeatureFlagManager {

	private const ENDPOINT_URL                      = 'https://pes-features-prod-pes.prod.packeta-com.codenow.com/v1/wp';
	private const VALID_FOR_SECONDS                 = 4 * HOUR_IN_SECONDS;
	public const FLAGS_OPTION_ID                    = 'packeta_feature_flags';
	private const TRANSIENT_SPLIT_MESSAGE_DISMISSED = 'packeta_split_message_dismissed';
	public const ACTION_HIDE_SPLIT_MESSAGE          = 'dismiss_split_message';
	public const DISABLED_DUE_ERRORS_OPTION_ID      = 'packeta_feature_flags_disabled_due_errors';
	private const ERROR_COUNTER_OPTION_ID           = 'packeta_feature_flags_error_counter';

	private const FLAG_LAST_DOWNLOAD = 'lastDownload';
	private const FLAG_SPLIT_ACTIVE  = 'splitActive';

	/**
	 * Static cache.
	 *
	 * @var array|false|null
	 */
	private static $flags;

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
	 * Helper.
	 *
	 * @var Module\Helper
	 */
	private $helper;

	/**
	 * WP adapter;
	 *
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * Downloader constructor.
	 *
	 * @param Engine        $latteEngine Latte engine.
	 * @param Provider      $optionsProvider Options provider.
	 * @param Module\Helper $helper Helper.
	 * @param WpAdapter     $wpAdapter WP adapter.
	 */
	public function __construct(
		Engine $latteEngine,
		Provider $optionsProvider,
		Module\Helper $helper,
		WpAdapter $wpAdapter
	) {
		$this->latteEngine     = $latteEngine;
		$this->optionsProvider = $optionsProvider;
		$this->helper          = $helper;
		$this->wpAdapter       = $wpAdapter;

		self::$flags = null;
	}

	/**
	 * Downloads flags.
	 *
	 * @return array
	 * @throws Exception From DateTimeImmutable.
	 */
	private function fetchFlags(): array {
		$response = $this->wpAdapter->remoteGet(
			$this->wpAdapter->addQueryArg( [ 'api_key' => $this->optionsProvider->get_api_key() ], self::ENDPOINT_URL ),
			[ 'timeout' => 20 ]
		);

		if ( $this->wpAdapter->isWpError( $response ) ) {
			$logger = new \WC_Logger();
			$logger->warning( 'Packeta Feature flag API download error: ' . $response->get_error_message() );
			$errorCount = $this->wpAdapter->getOption( self::ERROR_COUNTER_OPTION_ID, 0 );
			update_option( self::ERROR_COUNTER_OPTION_ID, $errorCount + 1 );
			if ( $errorCount > 5 ) {
				update_option( self::DISABLED_DUE_ERRORS_OPTION_ID, true );
				$logger->warning( 'Packeta Feature flag API download was disabled due to permanent connection errors.' );
			}

			return [];
		}

		$responseDecoded = json_decode( $this->wpAdapter->remoteRetrieveBody( $response ), true );
		$lastDownload    = new DateTimeImmutable( 'now', new \DateTimeZone( 'UTC' ) );
		$flags           = [
			self::FLAG_SPLIT_ACTIVE  => (bool) $responseDecoded['features']['split'],
			self::FLAG_LAST_DOWNLOAD => $lastDownload->format( Core\Helper::MYSQL_DATETIME_FORMAT ),
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
		if ( ! isset( self::$flags ) || null === self::$flags ) {
			self::$flags = $this->wpAdapter->getOption( self::FLAGS_OPTION_ID );
		}

		if ( true === $this->wpAdapter->getOption( self::DISABLED_DUE_ERRORS_OPTION_ID ) ) {
			return self::$flags ? self::$flags : [];
		}

		$hasApiKey = ( null !== $this->optionsProvider->get_api_key() );
		if ( false === self::$flags ) {
			if ( ! $hasApiKey ) {
				self::$flags = [];

				return self::$flags;
			}

			self::$flags = $this->fetchFlags();

			return self::$flags;
		}

		if ( $hasApiKey && ! $this->isLastDownloadValid() ) {
			self::$flags = $this->fetchFlags();
		}

		return self::$flags;
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
		$this->wpAdapter->setTransient( self::TRANSIENT_SPLIT_MESSAGE_DISMISSED, 'yes' );
	}

	/**
	 * Determines whether to display split notice.
	 *
	 * @return bool
	 */
	public function shouldShowSplitActivationNotice(): bool {
		return (
			$this->isSplitActive() &&
			'yes' !== $this->wpAdapter->getTransient( self::TRANSIENT_SPLIT_MESSAGE_DISMISSED )
		);
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
						...$this->helper->createLinkParts( 'https://github.com/Zasilkovna/WooCommerce/wiki', '_blank' ),
						...$this->helper->createLinkParts( $dismissUrl, null, 'button button-primary' )
					),
				],
			]
		);
	}

	/**
	 * Checks if last download is still valid.
	 *
	 * @return bool
	 */
	private function isLastDownloadValid(): bool {
		if ( ! isset( self::$flags[ self::FLAG_LAST_DOWNLOAD ] ) ) {
			// This should not happen, because the datetime is always set when fetching flags.
			// But we want it not to be prone to errors.
			return true;
		}

		$now        = new DateTimeImmutable( 'now', new \DateTimeZone( 'UTC' ) );
		$lastUpdate = DateTimeImmutable::createFromFormat(
			Core\Helper::MYSQL_DATETIME_FORMAT,
			self::$flags[ self::FLAG_LAST_DOWNLOAD ],
			new \DateTimeZone( 'UTC' )
		);

		return $now->getTimestamp() <= ( $lastUpdate->getTimestamp() + self::VALID_FOR_SECONDS );
	}

}
