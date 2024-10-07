<?php
/**
 * Class FeatureFlagProvider
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Options\FlagManager;

use Exception;
use Packetery\Module\Framework\WpAdapter;

/**
 * Class FeatureFlagProvider
 *
 * @package Packetery
 */
class FeatureFlagProvider {

	public const FLAG_SPLIT_ACTIVE  = 'splitActive';
	public const FLAG_LAST_DOWNLOAD = 'lastDownload';

	private const TRANSIENT_SPLIT_MESSAGE_DISMISSED = 'packeta_split_message_dismissed';


	/**
	 * WP adapter;
	 *
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * Feature flag downloader.
	 *
	 * @var FeatureFlagDownloader
	 */
	private $featureFlagDownloader;

	/**
	 * Constructor.
	 *
	 * @param WpAdapter             $wpAdapter WP adapter.
	 * @param FeatureFlagDownloader $featureFlagDownloader Feature flag downloader.
	 */
	public function __construct(
		WpAdapter $wpAdapter,
		FeatureFlagDownloader $featureFlagDownloader
	) {
		$this->wpAdapter             = $wpAdapter;
		$this->featureFlagDownloader = $featureFlagDownloader;
	}

	/**
	 * Tells if split is active.
	 *
	 * @return bool
	 * @throws Exception From DateTimeImmutable.
	 */
	public function isSplitActive(): bool {
		$flags = $this->featureFlagDownloader->getFlags();
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

}
