<?php
/**
 * Class Address
 *
 * @package Packetery\Module\EntityFactory
 */

declare( strict_types=1 );


namespace Packetery\Module\EntityFactory;

use Packetery\Core\Entity;
use Packetery\Module\Adapter;

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
		$meta    = Adapter::getAllUniquePostMeta( $addressId );

		$address = new Entity\Address( $meta['street'], $meta['city'], $meta['postCode'] );
		$address->setHouseNumber( $meta['houseNumber'] );

		return $address;
	}
}
