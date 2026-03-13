<?php

declare( strict_types=1 );

namespace Packetery\Module\Shipping;

use Packetery\Module\Carrier\Downloader;
use Packetery\Module\Carrier\PacketaPickupPointsConfig;

class ShippingMethodBulkGenerator {

	private PacketaPickupPointsConfig $pickupPointConfig;
	private Downloader $carrierDownloader;
	private ShippingMethodGenerator $shippingMethodGenerator;

	public function __construct(
		PacketaPickupPointsConfig $pickupPointConfig,
		Downloader $carrierDownloader,
		ShippingMethodGenerator $shippingMethodGenerator
	) {
		$this->pickupPointConfig       = $pickupPointConfig;
		$this->carrierDownloader       = $carrierDownloader;
		$this->shippingMethodGenerator = $shippingMethodGenerator;
	}

	public function generateClasses(): void {
		$allCarriers = [];

		$pickupPointCarriers = array_merge( $this->pickupPointConfig->getCompoundCarriers(), $this->pickupPointConfig->getVendorCarriers() );
		foreach ( $pickupPointCarriers as $pickupPointCarrier ) {
			$allCarriers[ $pickupPointCarrier->getId() ] = $pickupPointCarrier->getName();
		}

		$feedCarriers = $this->carrierDownloader->fetch_as_array( 'en' );
		if ( $feedCarriers === null ) {
			return;
		}
		foreach ( $feedCarriers as $feedCarrier ) {
			$allCarriers[ $feedCarrier['id'] ] = $feedCarrier['name'];
		}

		if ( count( $allCarriers ) === 0 ) {
			return;
		}

		foreach ( $allCarriers as $carrierId => $carrierName ) {
			if ( ! ShippingMethodGenerator::classExists( $carrierId ) ) {
				$this->shippingMethodGenerator->generateClass( $carrierId, $carrierName );
			}
		}
	}
}
