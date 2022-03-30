<?php
/**
 * Class Address
 *
 * @package Packetery\Module\EntityFactory
 */

declare( strict_types=1 );


namespace Packetery\Module\EntityFactory;

use Packetery\Core\Entity;

/**
 * Class Address
 *
 * @package Packetery\Module\EntityFactory
 */
class Address {

	/**
	 * Return WC store default address.
	 *
	 * @return Entity\Address
	 */
	public function fromWcStoreOptions(): Entity\Address {
		$address = new Entity\Address(
			get_option( 'woocommerce_store_address', null ),
			get_option( 'woocommerce_store_city', null ),
			get_option( 'woocommerce_store_postcode', null )
		);

		return $address;
	}
}
