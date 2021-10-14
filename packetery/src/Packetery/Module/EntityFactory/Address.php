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
		$metadata    = get_post_meta( $addressId );
		$street      = array_shift( $metadata['street'] );
		$city        = array_shift( $metadata['city'] );
		$zip         = array_shift( $metadata['postCode'] );
		$houseNumber = array_shift( $metadata['houseNumber'] );

		$address = new Entity\Address( $street, $city, $zip );
		$address->setHouseNumber( $houseNumber );

		return $address;
	}
}
