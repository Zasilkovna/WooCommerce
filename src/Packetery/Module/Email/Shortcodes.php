<?php

declare(strict_types=1);

namespace Packetery\Module\Email;

use Packetery\Core\Entity\Order;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Order\Repository;

class Shortcodes {
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
		$this->wpAdapter->addShortcode( 'packeta_if_submitted', [ $this, 'ifSubmitted' ] );
		$this->wpAdapter->addShortcode( 'packeta_if_pickup_point', [ $this, 'ifPickupPoint' ] );
	}

	private function getOrder( array $shortcodeAttributes ): ?Order {
		$orderId = $shortcodeAttributes['order_id'] ?? null;
		if ( ! $orderId ) {
			return null;
		}

		return $this->orderRepository->findById( (int) $orderId );
	}

	public function trackingNumber( array $shortcodeAttributes ): string {
		$order = $this->getOrder( $shortcodeAttributes );
		if ( ! $order ) {
			return '';
		}

		return $order->getPacketBarcode() ?? '';
	}

	public function trackingUrl( array $shortcodeAttributes ): string {
		$order = $this->getOrder( $shortcodeAttributes );
		if ( ! $order ) {
			return '';
		}

		$trackingNumber = $order->getPacketTrackingUrl();
		if ( ! $trackingNumber ) {
			return '';
		}

		return sprintf( 'https://tracking.packeta.com/Z%s', $trackingNumber );
	}

	public function pickupPointId( array $shortcodeAttributes ): string {
		$order = $this->getOrder( $shortcodeAttributes );
		if ( ! $order ) {
			return '';
		}

		$pickupPoint = $order->getPickupPoint();
		if ( ! $pickupPoint ) {
			return '';
		}

		return $pickupPoint->getId();
	}

	public function pickupPointName( array $shortcodeAttributes ): string {
		$order = $this->getOrder( $shortcodeAttributes );
		if ( ! $order ) {
			return '';
		}

		$pickupPoint = $order->getPickupPoint();
		if ( ! $pickupPoint ) {
			return '';
		}

		return $pickupPoint->getName();
	}

	public function pickupPointAddress( array $shortcodeAttributes ): string {
		$order = $this->getOrder( $shortcodeAttributes );
		if ( ! $order ) {
			return '';
		}

		$pickupPoint = $order->getPickupPoint();
		if ( ! $pickupPoint ) {
			return '';
		}

		return $pickupPoint->getFullAddress();
	}

	public function pickupPointStreet( array $shortcodeAttributes ): string {
		$order = $this->getOrder( $shortcodeAttributes );
		if ( ! $order ) {
			return '';
		}

		$pickupPoint = $order->getPickupPoint();
		if ( ! $pickupPoint ) {
			return '';
		}

		return $pickupPoint->getStreet();
	}

	public function pickupPointCity( array $shortcodeAttributes ): string {
		$order = $this->getOrder( $shortcodeAttributes );
		if ( ! $order ) {
			return '';
		}

		$pickupPoint = $order->getPickupPoint();
		if ( ! $pickupPoint ) {
			return '';
		}

		return $pickupPoint->getCity();
	}

	public function pickupPointZip( array $shortcodeAttributes ): string {
		$order = $this->getOrder( $shortcodeAttributes );
		if ( ! $order ) {
			return '';
		}

		$pickupPoint = $order->getPickupPoint();
		if ( ! $pickupPoint ) {
			return '';
		}

		return $pickupPoint->getZip();
	}

	public function pickupPointCountry( array $shortcodeAttributes ): string {
		$order = $this->getOrder( $shortcodeAttributes );
		if ( ! $order ) {
			return '';
		}

		$pickupPoint = $order->getPickupPoint();
		if ( ! $pickupPoint ) {
			return '';
		}

		return $order->getShippingCountry();
	}

	public function ifSubmitted( array $shortcodeAttributes, string $content = '' ): string {
		$order = $this->getOrder( $shortcodeAttributes );
		if ( ! $order || ! $order->getPacketId() ) {
			return '';
		}

		return $this->wpAdapter->doShortcode( $content );
	}

	public function ifPickupPoint( array $shortcodeAttributes, string $content = '' ): string {
		$order = $this->getOrder( $shortcodeAttributes );
		if ( ! $order || ! $order->getPickupPoint() ) {
			return '';
		}

		return $this->wpAdapter->doShortcode( $content );
	}
}
