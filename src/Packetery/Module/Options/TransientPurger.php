<?php
/**
 * Class TransientPurger.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Options;

use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Plugin;
use Packetery\Module\Transients;

/**
 * Class TransientPurger.
 *
 * @package Packetery
 */
class TransientPurger {
	/**
	 * @var Repository
	 */
	private $optionsRepository;

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	public function __construct( Repository $optionsRepository, WpAdapter $wpAdapter ) {
		$this->optionsRepository = $optionsRepository;
		$this->wpAdapter         = $wpAdapter;
	}

	/**
	 * Deletes expired transients.
	 *
	 * @return void
	 */
	public function purge(): void {
		if ( $this->wpAdapter->isMultisite() ) {
			$sites = Plugin::getSites();
			foreach ( $sites as $site ) {
				$this->wpAdapter->switchToBlog( $site );
				$this->purgeForSite();
				$this->wpAdapter->restoreCurrentBlog();
			}
		} else {
			$this->purgeForSite();
		}
	}

	private function purgeForSite(): void {
		$transients = $this->optionsRepository->getExpiredTransientsByPrefix( Transients::CHECKOUT_DATA_PREFIX );
		foreach ( $transients as $transient ) {
			$this->wpAdapter->deleteTransient( $transient );
		}
	}
}
