<?php
/**
 * Class ShippingProvider.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Shipping;

use Packetery\Core\Entity\Carrier;
use Packetery\Module\Carrier\PacketaPickupPointsConfig;
use Packetery\Module\Options\FeatureFlagManager;
use Packetery\Module\ShippingMethod;

/**
 * Class ShippingProvider.
 *
 * @package Packetery
 */
class ShippingProvider {

	/**
	 * Feature flag manager.
	 *
	 * @var FeatureFlagManager
	 */
	private $featureFlagManager;

	/**
	 * Pickup point config.
	 *
	 * @var PacketaPickupPointsConfig
	 */
	private $pickupPointConfig;

	/**
	 * Constructor.
	 *
	 * @param FeatureFlagManager        $featureFlagManager Feature flag manager.
	 * @param PacketaPickupPointsConfig $pickupPointConfig  Pickup point config.
	 */
	public function __construct(
		FeatureFlagManager $featureFlagManager,
		PacketaPickupPointsConfig $pickupPointConfig
	) {
		$this->featureFlagManager = $featureFlagManager;
		$this->pickupPointConfig  = $pickupPointConfig;
	}

	/**
	 * Loads generated shipping methods, including split ones when split is off.
	 *
	 * @return void
	 */
	public function loadAllClasses(): void {
		$generatedClassesPath = __DIR__ . '/Generated';
		foreach ( scandir( $generatedClassesPath ) as $filename ) {
			if ( preg_match( '/\.php$/', $filename ) ) {
				require_once $generatedClassesPath . '/' . $filename;
			}
		}
	}

	/**
	 * Loads generated shipping methods.
	 *
	 * @return void
	 */
	public function loadClasses(): void {
		$generatedClassesPath = __DIR__ . '/Generated';
		foreach ( scandir( $generatedClassesPath ) as $filename ) {
			if ( ! preg_match( '/\.php$/', $filename ) ) {
				continue;
			}
			if ( $this->featureFlagManager->isSplitActive() === false ) {
				$internalCountries = implode( '|', $this->pickupPointConfig->getInternalCountries() );
				$vendorGroups      = Carrier::VENDOR_GROUP_ZPOINT . '|' . Carrier::VENDOR_GROUP_ZBOX;
				if ( preg_match( '/^ShippingMethod_(' . $internalCountries . ')(' . $vendorGroups . ')\.php$/', $filename ) ) {
					continue;
				}
			}
			require_once $generatedClassesPath . '/' . $filename;
		}
	}

	/**
	 * Gets all full classnames of generated shipping methods.
	 *
	 * @return array
	 */
	public function getGeneratedClassnames(): array {
		$namespace = __NAMESPACE__ . '\Generated';

		return array_filter(
			get_declared_classes(),
			function ( $fullyQualifiedClassname ) use ( $namespace ) {
				return strpos( $fullyQualifiedClassname, $namespace ) === 0;
			}
		);
	}

	/**
	 * Checks if provided order uses our shipping method like native has_shipping_method method.
	 *
	 * @param \WC_Order $wcOrder WC order.
	 *
	 * @return bool
	 */
	public static function wcOrderHasOurMethod( \WC_Order $wcOrder ): bool {
		foreach ( $wcOrder->get_shipping_methods() as $shippingMethod ) {
			if ( self::isPacketaMethod( $shippingMethod->get_method_id() ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks if provided shipping method id belongs to one of Packeta methods.
	 *
	 * @param string $methodId Method id.
	 *
	 * @return bool
	 */
	public static function isPacketaMethod( string $methodId ): bool {
		return (
			ShippingMethod::PACKETERY_METHOD_ID === $methodId ||
			strpos( $methodId, BaseShippingMethod::PACKETA_METHOD_PREFIX ) === 0
		);
	}

}
