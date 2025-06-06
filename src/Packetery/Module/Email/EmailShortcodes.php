<?php

declare(strict_types=1);

namespace Packetery\Module\Email;

use Packetery\Core\Entity\Order;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Order\Repository;

class EmailShortcodes {
	/** @var WpAdapter */
	private $wpAdapter;

	/** @var Repository */
	private $orderRepository;

	public function __construct(
		WpAdapter $wpAdapter,
		Repository $orderRepository
	) {
		$this->wpAdapter       = $wpAdapter;
		$this->orderRepository = $orderRepository;
	}

	public function register(): void {
		$this->wpAdapter->addShortcode( 'packeta_tracking_number', [ $this, 'trackingNumber' ] );
		$this->wpAdapter->addShortcode( 'packeta_tracking_url', [ $this, 'trackingUrl' ] );
		$this->wpAdapter->addShortcode( 'packeta_pickup_point_id', [ $this, 'pickupPointId' ] );
		$this->wpAdapter->addShortcode( 'packeta_pickup_point_name', [ $this, 'pickupPointName' ] );
		$this->wpAdapter->addShortcode( 'packeta_pickup_point_address', [ $this, 'pickupPointAddress' ] );
		$this->wpAdapter->addShortcode( 'packeta_pickup_point_street', [ $this, 'pickupPointStreet' ] );
		$this->wpAdapter->addShortcode( 'packeta_pickup_point_city', [ $this, 'pickupPointCity' ] );
		$this->wpAdapter->addShortcode( 'packeta_pickup_point_zip', [ $this, 'pickupPointZip' ] );
		$this->wpAdapter->addShortcode( 'packeta_pickup_point_country', [ $this, 'pickupPointCountry' ] );
		$this->wpAdapter->addShortcode( 'packeta_carrier_name', [ $this, 'carrierName' ] );

		$this->wpAdapter->addShortcode( 'packeta_if_packet_submitted', [ $this, 'ifPacketSubmitted' ] );
		$this->wpAdapter->addShortcode( 'packeta_if_pickup_point', [ $this, 'ifPickupPoint' ] );
		$this->wpAdapter->addShortcode( 'packeta_if_carrier', [ $this, 'ifExternalCarrier' ] );
	}

	private function findOrder( array $shortcodeAttributes ): ?Order {
		$orderId = $shortcodeAttributes['order_id'] ?? null;
		if ( $orderId === null || is_numeric( $orderId ) === false ) {
			return null;
		}

		return $this->orderRepository->findById( (int) $orderId );
	}

	public function trackingNumber( array $shortcodeAttributes ): string {
		$order = $this->findOrder( $shortcodeAttributes );
		if ( $order === null ) {
			return '';
		}

		return $order->getPacketBarcode() ?? '';
	}

	public function trackingUrl( array $shortcodeAttributes ): string {
		$order = $this->findOrder( $shortcodeAttributes );
		if ( $order === null ) {
			return '';
		}

		return $order->getPacketTrackingUrl() ?? '';
	}

	public function pickupPointId( array $shortcodeAttributes ): string {
		$order = $this->findOrder( $shortcodeAttributes );
		if ( $order === null ) {
			return '';
		}

		$pickupPoint = $order->getPickupPoint();
		if ( $pickupPoint === null ) {
			return '';
		}

		return $pickupPoint->getId();
	}

	public function pickupPointName( array $shortcodeAttributes ): string {
		$order = $this->findOrder( $shortcodeAttributes );
		if ( $order === null ) {
			return '';
		}

		$pickupPoint = $order->getPickupPoint();
		if ( $pickupPoint === null ) {
			return '';
		}

		return $pickupPoint->getName();
	}

	public function pickupPointAddress( array $shortcodeAttributes ): string {
		$order = $this->findOrder( $shortcodeAttributes );
		if ( $order === null ) {
			return '';
		}

		$pickupPoint = $order->getPickupPoint();
		if ( $pickupPoint === null ) {
			return '';
		}

		return $pickupPoint->getFullAddress();
	}

	public function pickupPointStreet( array $shortcodeAttributes ): string {
		$order = $this->findOrder( $shortcodeAttributes );
		if ( $order === null ) {
			return '';
		}

		$pickupPoint = $order->getPickupPoint();
		if ( $pickupPoint === null ) {
			return '';
		}

		return $pickupPoint->getStreet();
	}

	public function pickupPointCity( array $shortcodeAttributes ): string {
		$order = $this->findOrder( $shortcodeAttributes );
		if ( $order === null ) {
			return '';
		}

		$pickupPoint = $order->getPickupPoint();
		if ( $pickupPoint === null ) {
			return '';
		}

		return $pickupPoint->getCity();
	}

	public function pickupPointZip( array $shortcodeAttributes ): string {
		$order = $this->findOrder( $shortcodeAttributes );
		if ( $order === null ) {
			return '';
		}

		$pickupPoint = $order->getPickupPoint();
		if ( $pickupPoint === null ) {
			return '';
		}

		return $pickupPoint->getZip();
	}

	public function pickupPointCountry( array $shortcodeAttributes ): string {
		$order = $this->findOrder( $shortcodeAttributes );
		if ( $order === null ) {
			return '';
		}

		$pickupPoint = $order->getPickupPoint();
		if ( $pickupPoint === null ) {
			return '';
		}

		return $order->getShippingCountry() ?? '';
	}

	public function carrierName( array $shortcodeAttributes ): string {
		$order = $this->findOrder( $shortcodeAttributes );
		if ( $order === null ) {
			return '';
		}

		return $order->getCarrier()->getName();
	}

	public function ifPacketSubmitted( array $shortcodeAttributes, string $content = '' ): string {
		$order = $this->findOrder( $shortcodeAttributes );
		if ( $order === null || $order->getPacketId() === null ) {
			return '';
		}

		return $this->wpAdapter->doShortcode( $content );
	}

	public function ifPickupPoint( array $shortcodeAttributes, string $content = '' ): string {
		$order = $this->findOrder( $shortcodeAttributes );
		if ( $order === null || $order->getPickupPoint() === null ) {
			return '';
		}

		return $this->wpAdapter->doShortcode( $content );
	}

	public function ifExternalCarrier( array $shortcodeAttributes, string $content = '' ): string {
		$order = $this->findOrder( $shortcodeAttributes );
		if ( $order === null ) {
			return '';
		}

		if ( $order->isExternalCarrier() === false ) {
			return '';
		}

		return $this->wpAdapter->doShortcode( $content );
	}
}
