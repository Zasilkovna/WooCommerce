<?php
/**
 * Class Entity
 *
 * @package Packetery\Order
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Module\Product;
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
	public const META_POINT_NAME       = 'packetery_point_name';
	public const META_POINT_URL        = 'packetery_point_url';
	public const META_POINT_STREET     = 'packetery_point_street';
	public const META_POINT_ZIP        = 'packetery_point_zip';
	public const META_POINT_CITY       = 'packetery_point_city';
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
	 * @return string|null
	 */
	public function getPointId(): ?string {
		return $this->getMetaAsNullableString( self::META_POINT_ID );
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
			$productWeight = (float) $product->get_weight();
			$weight       += ( $productWeight * $quantity );
		}

		return wc_get_weight( $weight, 'kg' );
	}

	/**
	 * Finds out if adult content is present.
	 *
	 * @return bool
	 */
	public function containsAdultContent(): bool {
		foreach ( $this->order->get_items() as $item ) {
			$itemData      = $item->get_data();
			$productEntity = Product\Entity::fromPostId( $itemData['product_id'] );
			if ( $productEntity->isAgeVerification18PlusRequired() ) {
				return true;
			}
		}

		return false;
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
	 * Gets order ID.
	 *
	 * @return int
	 */
	public function getId(): int {
		return $this->order->get_id();
	}
}
