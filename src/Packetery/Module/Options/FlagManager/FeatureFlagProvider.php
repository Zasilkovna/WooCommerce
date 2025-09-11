<?php
/**
 * Class FeatureFlagProvider
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Options\FlagManager;

use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Transients;

/**
 * Class FeatureFlagProvider
 *
 * @package Packetery
 */
class FeatureFlagProvider {

	public const FLAG_SPLIT_ACTIVE  = 'splitActive';
	public const FLAG_LAST_DOWNLOAD = 'lastDownload';

	/**
	 * WP adapter;
	 *
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * Constructor.
	 *
	 * @param WpAdapter $wpAdapter WP adapter.
	 */
	public function __construct(
		WpAdapter $wpAdapter
	) {
		$this->wpAdapter = $wpAdapter;
	}

	/**
	 * Tells if split is active.
	 *
	 * @return bool
	 */
	public function isSplitActive(): bool {
		// Enabled for all users. Method will be removed later.
		return true;
	}

	/**
	 * Dismiss split notice.
	 *
	 * @return void
	 */
	public function dismissSplitActivationNotice(): void {
		$this->wpAdapter->setTransient( Transients::SPLIT_MESSAGE_DISMISSED, 'yes' );
	}

	/**
	 * Determines whether to display split notice.
	 *
	 * @return bool
	 */
	public function shouldShowSplitActivationNotice(): bool {
		return (
			$this->isSplitActive() &&
			$this->wpAdapter->getTransient( Transients::SPLIT_MESSAGE_DISMISSED ) !== 'yes'
		);
	}
}
