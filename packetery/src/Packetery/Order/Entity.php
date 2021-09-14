<?php
/**
 * Class Entity
 *
 * @package Packetery\Order
 */

declare( strict_types=1 );


namespace Packetery\Order;

/**
 * Class Entity
 *
 * @package Packetery\Order
 */
class Entity {

	public const META_CARRIER_ID = 'packetery_carrier_id';
	public const META_IS_EXPORTED = 'packetery_is_exported';
	public const META_PACKET_ID = 'packetery_packet_id';

	/**
	 * Order.
	 *
	 * @var \WC_Order
	 */
	private $order;

	/**
	 * Entity constructor.
	 *
	 * @param \WC_Order $order Order.
	 */
	public function __construct( \WC_Order $order ) {
		$this->order = $order;
	}

	/**
	 * Creates value object from global variables.
	 *
	 * @param bool $needed
	 *
	 * @return static|null
	 */
	public static function from_globals( bool $needed = true ): ?self {
		global $post;
		$order = wc_get_order( $post->ID );

		if ( ! $needed && ! $order instanceof \WC_Order ) {
			return null;
		}

		return new self( $order );
	}

	/**
	 * Tells if order has Packeta shipping.
	 *
	 * @return bool
	 */
	public function is_packetery_related(): bool {
		return $this->order->has_shipping_method( 'packetery_shipping_method' ); // todo Move to service?
	}

	/**
	 * Tells if order is related to Packeta pickup point.
	 *
	 * @return bool
	 */
	public function isPacketeryPickupPointRelated(): bool {
		return $this->is_packetery_related() && null !== $this->get_point_id();
	}

	/**
	 * Gets meta from order and handles default value.
	 *
	 * @param string $key Meta order key.
	 *
	 * @return string|null
	 */
	private function get_meta_as_string( string $key ) {
		$value = $this->order->get_meta( $key, true );
		if ( ! $value ) {
			return null;
		}

		return (string) $value;
	}

	/**
	 * Selected pickup point ID
	 *
	 * @return string|null
	 */
	public function get_point_id(): ?string {
		return $this->get_meta_as_string( 'packetery_point_id' );
	}

	/**
	 * Packet ID
	 *
	 * @return string|null
	 */
	public function get_packet_id(): ?string {
		return $this->get_meta_as_string( self::META_PACKET_ID );
	}

	/**
	 * Point name.
	 *
	 * @return string|null
	 */
	public function get_point_name(): ?string {
		return $this->get_meta_as_string( 'packetery_point_name' );
	}

	/**
	 * Link to official Packeta detail page.
	 *
	 * @return string|null
	 */
	public function get_point_url(): ?string {
		return $this->get_meta_as_string( 'packetery_point_url' );
	}

	/**
	 * Dynamically crafted point address.
	 *
	 * @return string
	 */
	public function get_point_address(): string {
		return implode(
			', ',
			array_filter(
				array(
					$this->get_meta_as_string( 'packetery_point_street' ),
					implode(
						' ',
						array_filter(
							array(
								$this->get_meta_as_string( 'packetery_point_zip' ),
								$this->get_meta_as_string( 'packetery_point_city' ),
							)
						)
					),
				)
			)
		);
	}
}
