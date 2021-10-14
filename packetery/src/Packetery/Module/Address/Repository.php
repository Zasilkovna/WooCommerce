<?php
/**
 * Class Repository
 *
 * @package Packetery\Module\Address
 */

declare( strict_types=1 );


namespace Packetery\Module\Address;

use Packetery\Core\Entity;
use Packetery\Module\EntityFactory;

/**
 * Class Repository
 *
 * @package Packetery\Module\Address
 */
class Repository {
	public const POST_TYPE = 'packetery_address';

	/**
	 * Address factory.
	 *
	 * @var EntityFactory\Address
	 */
	private $addressFactory;

	/**
	 * Repository constructor.
	 *
	 * @param EntityFactory\Address $addressFactory Address factory.
	 */
	public function __construct( EntityFactory\Address $addressFactory ) {
		$this->addressFactory = $addressFactory;
	}

	/**
	 * Register post type used by repository.
	 */
	public function register(): void {
		$definition = [
			'labels'          => [ 'name' => __( 'Addresses', 'packetery' ) ],
			'public'          => false,
			'query_var'       => false,
			'rewrite'         => false,
			'capability_type' => 'post',
			'supports'        => [ 'title', 'editor' ],
			'can_export'      => false,
		];

		register_post_type( self::POST_TYPE, $definition );
	}

	/**
	 * Gets active address.
	 *
	 * @param int $orderId Order ID.
	 *
	 * @return Entity\Address|null
	 */
	public function getActiveByOrderId( int $orderId ): ?Entity\Address {
		$postIds = get_posts(
			[
				'post_type'   => self::POST_TYPE,
				'post_status' => 'any',
				'nopaging'    => true,
				'numberposts' => 1,
				'fields'      => 'ids',
				'post_parent' => $orderId,
				'meta_query'  => [
					[
						'key'   => 'active',
						'value' => '1',
					],
				],
			]
		);

		if ( empty( $postIds ) ) {
			return null;
		}

		$postId = array_shift( $postIds );
		return $this->addressFactory->fromPostId( $postId );
	}

	/**
	 * Saves active address for order.
	 *
	 * @param int   $orderId WC order id.
	 * @param array $data    Data.
	 */
	public function save( int $orderId, array $data ): void {
		$addressData = [
			'post_title'   => '', // required.
			'post_content' => '', // required.
			'post_type'    => self::POST_TYPE,
			'post_status'  => 'publish',
			'post_parent'  => $orderId,
		];

		$addressId = wp_insert_post( $addressData );

		foreach ( $data as $key => $value ) {
			update_post_meta( $addressId, $key, $value );
		}
	}
}
