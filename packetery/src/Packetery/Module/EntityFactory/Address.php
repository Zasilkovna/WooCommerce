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
