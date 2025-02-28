<?php

namespace Packetery\Module\Shipping;

use Packetery\Module\Carrier\Downloader;
use Packetery\Module\Carrier\PacketaPickupPointsConfig;
use Packetery\Nette\PhpGenerator\PhpFile;

class ShippingMethodGenerator {
	private const TARGET_NAMESPACE = 'Packetery\Module\Shipping\Generated';

	/**
	 * @var PacketaPickupPointsConfig
	 */
	private $pickupPointConfig;

	/**
	 * @var Downloader
	 */
	private $carrierDownloader;

	public function __construct(
		PacketaPickupPointsConfig $pickupPointConfig,
		Downloader $carrierDownloader
	) {
		$this->pickupPointConfig = $pickupPointConfig;
		$this->carrierDownloader = $carrierDownloader;
	}

	public function generateClasses(): void {
		$allCarriers = [];

		$pickupPointCarriers = array_merge( $this->pickupPointConfig->getCompoundCarriers(), $this->pickupPointConfig->getVendorCarriers( true ) );
		foreach ( $pickupPointCarriers as $pickupPointCarrier ) {
			$allCarriers[ $pickupPointCarrier->getId() ] = $pickupPointCarrier->getName();
		}

		$feedCarriers = $this->carrierDownloader->fetch_as_array( 'en' );
		foreach ( $feedCarriers as $feedCarrier ) {
			$allCarriers[ $feedCarrier['id'] ] = $feedCarrier['name'];
		}

		if ( count( $allCarriers ) === 0 ) {
			return;
		}

		foreach ( $allCarriers as $carrierId => $carrierName ) {
			if ( ! self::classExists( $carrierId ) ) {
				$this->generateClass( $carrierId, $carrierName );
			}
		}
	}

	/**
	 * @link https://doc.nette.org/en/php-generator
	 */
	private function generateClass( string $carrierId, string $carrierName ): void {
		$file = new PhpFile();
		$file->addComment( 'This file is auto-generated.' );
		$file->setStrictTypes();

		$namespace = $file->addNamespace( self::TARGET_NAMESPACE );
		$namespace->addUse( BaseShippingMethod::class );

		$className = self::getClassnameFromCarrierId( $carrierId );
		$class     = $namespace->addClass( $className );
		$class->setExtends( BaseShippingMethod::class )->addComment( $carrierName );

		// Add setType later.
		$class->addConstant( 'CARRIER_ID', $carrierId )->setPublic();

		$filePath = __DIR__ . '/Generated/' . $className . '.php';

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $filePath, $file );
	}

	private static function getClassnameFromCarrierId( string $carrierId ): string {
		return 'ShippingMethod_' . $carrierId;
	}

	public static function classExists( string $carrierId ): bool {
		return class_exists( self::TARGET_NAMESPACE . '\\' . self::getClassnameFromCarrierId( $carrierId ) );
	}
}
