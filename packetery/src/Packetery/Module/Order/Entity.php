<?php
/**
 * Class Entity
 *
 * @package Packetery\Order
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Module\Carrier\Repository;
use Packetery\Module\ShippingMethod;
use WC_Order;

/**
 * Class Entity
 *
 * @package Packetery\Order
 */
class Entity {

	public const META_CARRIER_ID       = 'packetery_carrier_id';
	public const META_IS_EXPORTED      = 'packetery_is_exported';
	public const META_PACKET_ID        = 'packetery_packet_id';
	public const META_IS_LABEL_PRINTED = 'packetery_is_label_printed';
	public const META_POINT_ID         = 'packetery_point_id';
	public const META_POINT_CARRIER_ID = 'packetery_point_carrier_id';
	public const META_POINT_NAME       = 'packetery_point_name';
	public const META_POINT_URL        = 'packetery_point_url';
	public const META_POINT_STREET     = 'packetery_point_street';
	public const META_POINT_ZIP        = 'packetery_point_zip';
	public const META_POINT_CITY       = 'packetery_point_city';
	public const META_POINT_TYPE       = 'packetery_point_type';
	public const META_WEIGHT           = 'packetery_weight';
	public const META_LENGTH           = 'packetery_length';
	public const META_WIDTH            = 'packetery_width';
	public const META_HEIGHT           = 'packetery_height';
	public const META_CARRIER_NUMBER   = 'packetery_carrier_number';

	/**
	 * Order.
	 *
	 * @var WC_Order
	 */
	private $order;

	/**
	 * Entity constructor.
	 *
	 * @param WC_Order $order Order.
	 */
	public function __construct( WC_Order $order ) {
		$this->order = $order;
	}

	/**
	 * Creates value object from global variables.
	 *
	 * @return static|null
	 */
	public static function fromGlobals(): ?self {
		global $post;

		return self::fromPostId( $post->ID );
	}

	/**
	 * Creates value object from post id.
	 *
	 * @param int|string $postId Post id.
	 *
	 * @return static|null
	 */
	public static function fromPostId( $postId ): ?self {
		$order = wc_get_order( $postId );

		if ( ! $order instanceof WC_Order ) {
			return null;
		}

		return new self( $order );
	}

	/**
	 * Tells if order has Packeta shipping.
	 *
	 * @return bool
	 */
	public function isPacketeryRelated(): bool {
		return $this->order->has_shipping_method( ShippingMethod::PACKETERY_METHOD_ID ); // todo Move to service?
	}

	/**
	 * Tells if order is related to Packeta pickup point.
	 *
	 * @return bool
	 */
	public function isPacketeryPickupPointRelated(): bool {
		return $this->isPacketeryRelated() && null !== $this->getPointId();
	}

	/**
	 * Gets meta property of order as string.
	 *
	 * @param string $key Meta order key.
	 *
	 * @return string|null
	 */
	private function getMetaAsNullableString( string $key ): ?string {
		$value = $this->order->get_meta( $key, true );

		return ( ( null !== $value && '' !== $value ) ? (string) $value : null );
	}

	/**
	 * Gets meta property of order as float.
	 *
	 * @param string $key Meta order key.
	 *
	 * @return float|null
	 */
	private function getMetaAsNullableFloat( string $key ): ?float {
		$value = $this->order->get_meta( $key, true );

		return ( ( null !== $value && '' !== $value ) ? (float) $value : null );
	}

	/**
	 * Gets order post id.
	 *
	 * @return int|null
	 */
	public function getPostId(): ?int {
		return $this->order->get_id();
	}

	/**
	 * Selected pickup point ID
	 *
	 * @return int|null
	 */
	public function getPointId(): ?int {
		$value = $this->getMetaAsNullableString( self::META_POINT_ID );

		return ( null !== $value ? (int) $value : null );
	}

	/**
	 * Packet ID
	 *
	 * @return string|null
	 */
	public function getPacketId(): ?string {
		return $this->getMetaAsNullableString( self::META_PACKET_ID );
	}

	/**
	 * Point name.
	 *
	 * @return string|null
	 */
	public function getPointName(): ?string {
		return $this->getMetaAsNullableString( self::META_POINT_NAME );
	}

	/**
	 * Link to official Packeta detail page.
	 *
	 * @return string|null
	 */
	public function getPointUrl(): ?string {
		return $this->getMetaAsNullableString( self::META_POINT_URL );
	}

	/**
	 * Point street.
	 *
	 * @return string|null
	 */
	public function getPointStreet(): ?string {
		return $this->getMetaAsNullableString( self::META_POINT_STREET );
	}

	/**
	 * Point city.
	 *
	 * @return string|null
	 */
	public function getPointCity(): ?string {
		return $this->getMetaAsNullableString( self::META_POINT_CITY );
	}

	/**
	 * Point zip.
	 *
	 * @return string|null
	 */
	public function getPointZip(): ?string {
		return $this->getMetaAsNullableString( self::META_POINT_ZIP );
	}

	/**
	 * Gets carrier id.
	 *
	 * @return string|null
	 */
	public function getCarrierId(): ?string {
		return $this->getMetaAsNullableString( self::META_CARRIER_ID );
	}

	/**
	 * Gets carrier pickup point id.
	 *
	 * @return string|null
	 */
	public function getPointCarrierId(): ?string {
		return $this->getMetaAsNullableString( self::META_POINT_CARRIER_ID );
	}

	/**
	 * Gets pickup point type.
	 *
	 * @return string|null
	 */
	public function getPointType(): ?string {
		return $this->getMetaAsNullableString( self::META_POINT_TYPE );
	}

	/**
	 * Gets packet carrier number.
	 *
	 * @return string|null
	 */
	public function getCarrierNumber(): ?string {
		return $this->getMetaAsNullableString( self::META_CARRIER_NUMBER );
	}

	/**
	 * Tells if is packet submitted.
	 *
	 * @return bool
	 */
	public function isExported(): bool {
		return (bool) $this->getMetaAsNullableString( self::META_IS_EXPORTED );
	}

	/**
	 * Gets weight.
	 *
	 * @return float|null
	 */
	public function getWeight(): ?float {
		$metaWeight = $this->getMetaAsNullableFloat( self::META_WEIGHT );
		if ( $metaWeight ) {
			return $metaWeight;
		}

		$weight = 0;
		foreach ( $this->order->get_items() as $item ) {
			$quantity      = $item->get_quantity();
			$product       = $item->get_product();
			$productWeight = $product->get_weight();
			$weight       += ( $productWeight * $quantity );
		}

		return wc_get_weight( $weight, 'kg' );
	}

	/**
	 * Gets length.
	 *
	 * @return float|null
	 */
	public function getLength(): ?float {
		return $this->getMetaAsNullableFloat( self::META_LENGTH );
	}

	/**
	 * Gets width.
	 *
	 * @return float|null
	 */
	public function getWidth(): ?float {
		return $this->getMetaAsNullableFloat( self::META_WIDTH );
	}

	/**
	 * Gets height.
	 *
	 * @return float|null
	 */
	public function getHeight(): ?float {
		return $this->getMetaAsNullableFloat( self::META_HEIGHT );
	}

	/**
	 * Checks if is home delivery. In that case pointId is not set.
	 *
	 * @return bool
	 */
	public function isHomeDelivery(): bool {
		return ( $this->getCarrierId() !== null && $this->getPointType() === null );
	}

	/**
	 * Checks if is external pickup point delivery. In that case, pointCarrierId is set.
	 *
	 * @return bool
	 */
	public function isExternalPickupPointDelivery(): bool {
		$ppType = $this->getPointType();

		return ( $ppType && 'external' === $ppType );
	}

	/**
	 * Checks if uses external carrier.
	 *
	 * @return bool
	 */
	public function isExternalCarrier(): bool {
		return ( Repository::INTERNAL_PICKUP_POINTS_ID !== $this->getCarrierId() );
	}

	/**
	 * Checks if uses internal pickup point.
	 *
	 * @return bool
	 */
	public function isInternalPickupPointDelivery(): bool {
		return ( Repository::INTERNAL_PICKUP_POINTS_ID === $this->getCarrierId() );
	}

	/**
	 * Gets pickup point/carrier id.
	 *
	 * @return int
	 */
	public function getAddressId(): int {
		if ( $this->isExternalPickupPointDelivery() || $this->isHomeDelivery() ) {
			return (int) $this->getCarrierId();
		}

		return (int) $this->getPointId();
	}
}
