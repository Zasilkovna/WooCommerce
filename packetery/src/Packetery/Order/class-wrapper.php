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
class Wrapper {

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
	 * @return static
	 */
	public static function from_globals(): self {
		global $post;
		$order = wc_get_order( $post->ID );

		return new self( $order );
	}

	/**
	 * Gets meta from order and handles default value.
	 *
	 * @param string $key Meta order key.
	 *
	 * @return string|null
	 */
	private function get_meta( string $key ) {
		$value = $this->order->get_meta( $key, true );
		if ( ! $value ) {
			return null;
		}

		return (string) $value;
	}

	/**
	 * Packet ID
	 *
	 * @return string|null
	 */
	public function get_packet_id(): ?string {
		return $this->get_meta( 'packetery_packet_id' );
	}

	/**
	 * Point name.
	 *
	 * @return string|null
	 */
	public function get_point_name(): ?string {
		return $this->get_meta( 'packetery_point_name' );
	}

	/**
	 * Link to official Packeta detail page.
	 *
	 * @return string|null
	 */
	public function get_point_url(): ?string {
		return $this->get_meta( 'packetery_point_url' );
	}

	/**
	 * Dynamically crafted point address.
	 *
	 * @return string
	 */
	public function get_point_address(): string {
		return implode(
			' ',
			array_filter(
				array(
					$this->get_meta( 'packetery_point_street' ),
					$this->get_meta( 'packetery_point_city' ),
					$this->get_meta( 'packetery_point_zip' ),
				)
			)
		);
	}
}
