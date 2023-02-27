<?php
/**
 * Class Entity
 *
 * @package Packetery\Module\Carrier
 */

declare( strict_types=1 );


namespace Packetery\Module\Carrier;

use Packetery\Core\Entity\Carrier;
use Packetery\Module\ShippingFacade;

/**
 * Class Entity
 *
 * @package Packetery\Module\Carrier
 */
class Entity {

	/**
	 * Core Carrier.
	 *
	 * @var Carrier
	 */
	private $carrier;

	/**
	 * Entity constructor.
	 *
	 * @param Carrier $carrier Carrier.
	 */
	public function __construct( Carrier $carrier ) {
		$this->carrier = $carrier;
	}

	/**
	 * Gets options.
	 */
	private function getOptions(): array {
		$options = get_option( ShippingFacade::CARRIER_PREFIX . $this->carrier->getId() );
		if ( ! $options ) {
			return [];
		}

		return $options;
	}

	/**
	 * Gets final carrier name.
	 */
	public function getFinalName(): string {
		$options    = $this->getOptions();
		$customName = ( $options[ OptionsPage::FORM_FIELD_NAME ] ?? null );
		if ( $customName ) {
			return $customName;
		}

		return $this->carrier->getName();
	}
}
