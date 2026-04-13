<?php

declare( strict_types=1 );

namespace Packetery\Module\Shipping;

use Packetery\Module\Carrier\Downloader;
use Packetery\Module\Carrier\PacketaPickupPointsConfig;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\ModuleHelper;

class ShippingMethodBulkGenerator {

	private PacketaPickupPointsConfig $pickupPointConfig;
	private Downloader $carrierDownloader;
	private ShippingMethodGenerator $shippingMethodGenerator;
	private WpAdapter $wpAdapter;

	public function __construct(
		PacketaPickupPointsConfig $pickupPointConfig,
		Downloader $carrierDownloader,
		ShippingMethodGenerator $shippingMethodGenerator,
		WpAdapter $wpAdapter
	) {
		$this->pickupPointConfig       = $pickupPointConfig;
		$this->carrierDownloader       = $carrierDownloader;
		$this->shippingMethodGenerator = $shippingMethodGenerator;
		$this->wpAdapter               = $wpAdapter;
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

		if ( $allCarriers === [] ) {
			return;
		}

		$carrierCount = count( $allCarriers );
		ModuleHelper::renderString( "Carrier count: $carrierCount\n" );

		$targetDirectory = ShippingMethodGenerator::getTargetDirectory();
		if ( $this->wpAdapter->isWritable( $targetDirectory ) === false ) {
			ModuleHelper::renderString( "Shipping method classes directory $targetDirectory is not writable.\n" );

			return;
		}

		$carriersGenerated    = [];
		$carriersNotGenerated = [];
		foreach ( $allCarriers as $carrierId => $carrierName ) {
			$carrierId = (string) $carrierId;
			if ( ShippingMethodGenerator::classExists( $carrierId ) ) {
				continue;
			}
			$generateClassResult = $this->shippingMethodGenerator->generateClass( $carrierId, $carrierName );
			$carrierInfo         = "$carrierName ($carrierId)";
			if ( $generateClassResult === false ) {
				$carriersNotGenerated[] = $carrierInfo;
			} else {
				$carriersGenerated[] = $carrierInfo;
			}
		}

		if ( $carriersGenerated !== [] ) {
			ModuleHelper::renderString( "Classes have been generated for the following carriers:\n" . implode( "\n", $carriersGenerated ) . "\n" );
		}
		if ( $carriersNotGenerated !== [] ) {
			ModuleHelper::renderString( "The classes for the following carriers could not be generated, the folder is probably not writable:\n" . implode( "\n", $carriersNotGenerated ) . "\n" );
		}
		if ( $carriersGenerated === [] && $carriersNotGenerated === [] ) {
			ModuleHelper::renderString( "All classes have already been generated.\n" );
		}
		ModuleHelper::renderString( "\n" );
	}
}
