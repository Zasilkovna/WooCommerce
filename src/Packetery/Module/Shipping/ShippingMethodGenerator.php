<?php
/**
 * Class ShippingMethodGenerator.
 *
 * @package Packetery
 */

namespace Packetery\Module\Shipping;

use Packetery\Module\Carrier\Downloader;
use Packetery\Module\Carrier\PacketaPickupPointsConfig;
use Packetery\Nette\PhpGenerator\PhpFile;

/**
 * Class ShippingMethodGenerator.
 *
 * @package Packetery
 */
class ShippingMethodGenerator {
	private const TARGET_NAMESPACE = 'Packetery\Module\Shipping\Generated';

	/**
	 * Pickup point config.
	 *
	 * @var PacketaPickupPointsConfig
	 */
	private $pickupPointConfig;

	/**
	 * Carrier downloader.
	 *
	 * @var Downloader
	 */
	private $carrierDownloader;

	/**
	 * Constructor.
	 *
	 * @param PacketaPickupPointsConfig $pickupPointConfig Pickup point config.
	 * @param Downloader                $carrierDownloader Carrier downloader.
	 */
	public function __construct(
		PacketaPickupPointsConfig $pickupPointConfig,
		Downloader $carrierDownloader
	) {
		$this->pickupPointConfig = $pickupPointConfig;
		$this->carrierDownloader = $carrierDownloader;
	}

	/**
	 * Generates classes for all carriers in feed.
	 *
	 * @return void
	 */
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
	 * Generates class source code.
	 *
	 * @link https://doc.nette.org/en/php-generator
	 *
	 * @param string $carrierId   Carrier id.
	 * @param string $carrierName Carrier name.
	 *
	 * @return void
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

	/**
	 * Returns carrier shipping method classname.
	 *
	 * @param string $carrierId Carrier id.
	 *
	 * @return string
	 */
	private static function getClassnameFromCarrierId( string $carrierId ): string {
		return 'ShippingMethod_' . $carrierId;
	}

	/**
	 * Checks if carrier class exists.
	 *
	 * @param string $carrierId Carrier id.
	 *
	 * @return bool
	 */
	public static function classExists( string $carrierId ): bool {
		return class_exists( self::TARGET_NAMESPACE . '\\' . self::getClassnameFromCarrierId( $carrierId ) );
	}
}
