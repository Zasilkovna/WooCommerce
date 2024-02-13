<?php
/**
 * Class TransientPurger.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Options;

use Packetery\Module\Checkout;
use Packetery\Module\Plugin;

/**
 * Class TransientPurger.
 *
 * @package Packetery
 */
class TransientPurger {

	/**
	 * Repository.
	 *
	 * @var Repository
	 */
	private $optionsRepository;

	/**
	 * Constructor.
	 *
	 * @param Repository $optionsRepository Repository.
	 */
	public function __construct( Repository $optionsRepository ) {
		$this->optionsRepository = $optionsRepository;
	}

	/**
	 * Deletes expired transients.
	 *
	 * @return void
	 */
	public function purge(): void {
		if ( is_multisite() ) {
			$sites = Plugin::getSites();
			foreach ( $sites as $site ) {
				switch_to_blog( $site );
				$this->optionsRepository->deleteExpiredTransientsByPrefix( Checkout::TRANSIENT_CHECKOUT_DATA_PREFIX );
				restore_current_blog();
			}
		} else {
			$this->optionsRepository->deleteExpiredTransientsByPrefix( Checkout::TRANSIENT_CHECKOUT_DATA_PREFIX );
		}
	}

}
