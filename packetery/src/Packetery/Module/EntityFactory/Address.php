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
		$meta = Adapter::getAllUniquePostMeta( $addressId );

		$address = new Entity\Address( $meta['street'], $meta['city'], $meta['postCode'] );
		$address->setHouseNumber( $meta['houseNumber'] );

		return $address;
	}

	/**
	 * Create address object from post using checkout attributes.
	 *
	 * @param array $post       POST data.
	 * @param array $attributes Attributes.
	 *
	 * @return Entity\Address
	 */
	public function fromPostUsingCheckoutAttributes( array $post, array $attributes ): Entity\Address {
		$address = new Entity\Address(
			( $post[ $attributes['street']['name'] ] ?? null ),
			( $post[ $attributes['city']['name'] ] ?? null ),
			( $post[ $attributes['postCode']['name'] ] ?? null )
		);

		$houseNumber = ( $post[ $attributes['houseNumber']['name'] ] ?? null );
		if ( $houseNumber ) {
			$address->setHouseNumber( $houseNumber );
		}

		return $address;
	}
}
