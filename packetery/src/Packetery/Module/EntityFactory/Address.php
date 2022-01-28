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
	 * Creates active widget address using woocommerce order id.
	 *
	 * @param int $addressId Address ID.
	 *
	 * @return Entity\Address|null
	 */
	public function fromPostId( int $addressId ): ?Entity\Address {
		$address = new Entity\Address(
			get_post_meta( $addressId, 'street', true ),
			get_post_meta( $addressId, 'city', true ),
			get_post_meta( $addressId, 'postCode', true )
		);
		$address->setHouseNumber( get_post_meta( $addressId, 'houseNumber', true ) );
		$address->setCounty( get_post_meta( $addressId, 'county', true ) );
		$address->setLongitude( get_post_meta( $addressId, 'longitude', true ) );
		$address->setLatitude( get_post_meta( $addressId, 'latitude', true ) );

		return $address;
	}

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
