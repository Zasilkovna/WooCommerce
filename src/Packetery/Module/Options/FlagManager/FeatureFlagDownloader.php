<?php
/**
 * Class FeatureFlagDownloader
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Options\FlagManager;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Packetery\Core\CoreHelper;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionsProvider;

/**
 * Class FeatureFlagDownloader
 *
 * @package Packetery
 */
class FeatureFlagDownloader {

	private const VALID_FOR_SECONDS            = 4 * HOUR_IN_SECONDS;
	public const FLAGS_OPTION_ID               = 'packeta_feature_flags';
	public const DISABLED_DUE_ERRORS_OPTION_ID = 'packeta_feature_flags_disabled_due_errors';
	private const ERROR_COUNTER_OPTION_ID      = 'packeta_feature_flags_error_counter';

	/**
	 * Endpoint url.
	 *
	 * @var string
	 */
	private $endpointUrl;

	/**
	 * Options provider.
	 *
	 * @var OptionsProvider
	 */
	private $optionsProvider;

	/**
	 * WP adapter;
	 *
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * WC adapter.
	 *
	 * @var WcAdapter
	 */
	private $wcAdapter;

	/**
	 * Feature flag store.
	 *
	 * @var FeatureFlagStorage
	 */
	private $featureFlagStorage;

	/**
	 * Downloader constructor.
	 *
	 * @param string             $endpointUrl        Endpoint url.
	 * @param OptionsProvider    $optionsProvider    Options provider.
	 * @param WpAdapter          $wpAdapter          WP adapter.
	 * @param WcAdapter          $wcAdapter          WC adapter.
	 * @param FeatureFlagStorage $featureFlagStorage Feature flag store.
	 */
	public function __construct(
		string $endpointUrl,
		OptionsProvider $optionsProvider,
		WpAdapter $wpAdapter,
		WcAdapter $wcAdapter,
		FeatureFlagStorage $featureFlagStorage
	) {
		$this->endpointUrl        = $endpointUrl;
		$this->optionsProvider    = $optionsProvider;
		$this->wpAdapter          = $wpAdapter;
		$this->wcAdapter          = $wcAdapter;
		$this->featureFlagStorage = $featureFlagStorage;
	}

	/**
	 * Downloads flags.
	 *
	 * @return array
	 * @throws Exception From DateTimeImmutable.
	 */
	private function fetchFlags(): array {
		$response = $this->wpAdapter->remoteGet(
			$this->wpAdapter->addQueryArg( [ 'api_key' => $this->optionsProvider->get_api_key() ], $this->endpointUrl ),
			[ 'timeout' => 20 ]
		);

		if ( $this->wpAdapter->isWpError( $response ) ) {
			$logger = $this->wcAdapter->createLogger();
			$logger->warning( 'Packeta Feature flag API download error: ' . $response->get_error_message() );
			$errorCount = $this->wpAdapter->getOption( self::ERROR_COUNTER_OPTION_ID, 0 );
			$this->wpAdapter->updateOption( self::ERROR_COUNTER_OPTION_ID, $errorCount + 1 );
			if ( $errorCount > 5 ) {
				$this->wpAdapter->updateOption( self::DISABLED_DUE_ERRORS_OPTION_ID, true );
				$logger->warning( 'Packeta Feature flag API download was disabled due to permanent connection errors.' );
			}

			return [];
		}

		$responseDecoded = json_decode( $this->wpAdapter->remoteRetrieveBody( $response ), true );
		$lastDownload    = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
		$flags           = [
			FeatureFlagProvider::FLAG_SPLIT_ACTIVE  => (bool) $responseDecoded['features']['split'],
			FeatureFlagProvider::FLAG_LAST_DOWNLOAD => $lastDownload->format( CoreHelper::MYSQL_DATETIME_FORMAT ),
		];

		$this->wpAdapter->updateOption( self::FLAGS_OPTION_ID, $flags );
		$this->wpAdapter->updateOption( self::ERROR_COUNTER_OPTION_ID, 0 );

		return $flags;
	}

	/**
	 * Gets or downloads flags.
	 *
	 * @return array
	 * @throws Exception From DateTimeImmutable.
	 */
	public function getFlags(): array {
		if ( empty( $this->featureFlagStorage->getFlags() ) ) {
			$flagsFromOptions = $this->wpAdapter->getOption( self::FLAGS_OPTION_ID );
			if ( is_array( $flagsFromOptions ) ) {
				$this->featureFlagStorage->setFlags( $flagsFromOptions );
			}
		}

		if ( true === $this->wpAdapter->getOption( self::DISABLED_DUE_ERRORS_OPTION_ID ) ) {
			return $this->featureFlagStorage->getFlags() ? $this->featureFlagStorage->getFlags() : [];
		}

		$hasApiKey = ( null !== $this->optionsProvider->get_api_key() );
		if ( null === $this->featureFlagStorage->getFlags() ) {
			if ( ! $hasApiKey ) {
				$this->featureFlagStorage->setFlags( [] );

				return $this->featureFlagStorage->getFlags();
			}

			$this->featureFlagStorage->setFlags( $this->fetchFlags() );

			return $this->featureFlagStorage->getFlags();
		}

		if ( $hasApiKey && ! $this->isLastDownloadValid() ) {
			$this->featureFlagStorage->setFlags( $this->fetchFlags() );
		}

		return $this->featureFlagStorage->getFlags();
	}

	/**
	 * Checks if last download is still valid.
	 *
	 * @return bool
	 */
	private function isLastDownloadValid(): bool {
		if ( null === $this->featureFlagStorage->getFlag( FeatureFlagProvider::FLAG_LAST_DOWNLOAD ) ) {
			// This should not happen, because the datetime is always set when fetching flags.
			// But we want it not to be prone to errors.
			return true;
		}

		$now        = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
		$lastUpdate = DateTimeImmutable::createFromFormat(
			CoreHelper::MYSQL_DATETIME_FORMAT,
			$this->featureFlagStorage->getFlag( FeatureFlagProvider::FLAG_LAST_DOWNLOAD ),
			new DateTimeZone( 'UTC' )
		);

		return $now->getTimestamp() <= ( $lastUpdate->getTimestamp() + self::VALID_FOR_SECONDS );
	}

}
