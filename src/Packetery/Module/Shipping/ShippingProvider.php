<?php
/**
 * Class ShippingProvider.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Shipping;

use Packetery\Core\Entity\Carrier;
use Packetery\Module\Carrier\CarDeliveryConfig;
use Packetery\Module\Carrier\CarrierOptionsFactory;
use Packetery\Module\Carrier\EntityRepository;
use Packetery\Module\Carrier\PacketaPickupPointsConfig;
use Packetery\Module\ContextResolver;
use Packetery\Module\Options\FeatureFlagManager;
use Packetery\Module\ShippingMethod;
use Packetery\Module\ShippingZoneRepository;

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
	 * Car delivery config.
	 *
	 * @var CarDeliveryConfig
	 */
	private $carDeliveryConfig;

	/**
	 * Context resolver.
	 *
	 * @var ContextResolver
	 */
	private $contextResolver;

	/**
	 * Shipping zone repository.
	 *
	 * @var ShippingZoneRepository
	 */
	private $shippingZoneRepository;

	/**
	 * Entity repository.
	 *
	 * @var EntityRepository
	 */
	private $carrierRepository;

	/**
	 * Carrier options factory.
	 *
	 * @var CarrierOptionsFactory
	 */
	private $carrierOptionsFactory;

	/**
	 * Constructor.
	 *
	 * @param FeatureFlagManager        $featureFlagManager     Feature flag manager.
	 * @param PacketaPickupPointsConfig $pickupPointConfig      Pickup point config.
	 * @param CarDeliveryConfig         $carDeliveryConfig      Car delivery config.
	 * @param ContextResolver           $contextResolver        Context resolver.
	 * @param ShippingZoneRepository    $shippingZoneRepository Shipping zone repository.
	 * @param EntityRepository          $carrierRepository      Carrier repository.
	 * @param CarrierOptionsFactory     $carrierOptionsFactory  Carrier options factory.
	 */
	public function __construct(
		FeatureFlagManager $featureFlagManager,
		PacketaPickupPointsConfig $pickupPointConfig,
		CarDeliveryConfig $carDeliveryConfig,
		ContextResolver $contextResolver,
		ShippingZoneRepository $shippingZoneRepository,
		EntityRepository $carrierRepository,
		CarrierOptionsFactory $carrierOptionsFactory
	) {
		$this->featureFlagManager     = $featureFlagManager;
		$this->pickupPointConfig      = $pickupPointConfig;
		$this->carDeliveryConfig      = $carDeliveryConfig;
		$this->contextResolver        = $contextResolver;
		$this->shippingZoneRepository = $shippingZoneRepository;
		$this->carrierRepository      = $carrierRepository;
		$this->carrierOptionsFactory  = $carrierOptionsFactory;
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
			if ( $this->carDeliveryConfig->isDisabled() ) {
				$carDeliveryIds = implode( '|', Carrier::CAR_DELIVERY_CARRIERS );
				if ( preg_match( '/^ShippingMethod_(' . $carDeliveryIds . ')\.php$/', $filename ) ) {
					continue;
				}
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
	private function getGeneratedClassnames(): array {
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

	/**
	 * Loads shipping methods, uses different approach for zone setting page.
	 *
	 * @param array $methods Previous state.
	 *
	 * @return array
	 */
	public function addMethods( array $methods ): array {
		$zoneId = $this->contextResolver->getShippingZoneId();
		if ( null === $zoneId ) {
			return $this->addAllMethods( $methods );
		}

		$allowedCountries = $this->shippingZoneRepository->getCountryCodesForShippingZone( $zoneId );
		if ( [] === $allowedCountries ) {
			return $this->addAllMethods( $methods );
		}

		foreach ( $allowedCountries as $countryCode ) {
			$carriers = $this->carrierRepository->getByCountryIncludingNonFeed( $countryCode );
			foreach ( $carriers as $carrier ) {
				foreach ( $this->getGeneratedClassnames() as $fullyQualifiedClassname ) {
					if ( $carrier->getId() === $fullyQualifiedClassname::CARRIER_ID ) {
						$methods = $this->addActiveCarrierMethod( $fullyQualifiedClassname, $methods );
						break;
					}
				}
			}
		}

		return $methods;
	}

	/**
	 * Add shipping methods to array.
	 *
	 * @param array $methods Array.
	 *
	 * @return array
	 */
	private function addAllMethods( array $methods ): array {
		foreach ( $this->getGeneratedClassnames() as $fullyQualifiedClassname ) {
			$methods = $this->addActiveCarrierMethod( $fullyQualifiedClassname, $methods );
		}

		return $methods;
	}

	/**
	 * Adds shipping method to array in case the carrier is active.
	 *
	 * @param string $fullyQualifiedClassname Fully qualified classname.
	 * @param array  $methods                 Methods.
	 *
	 * @return array
	 */
	private function addActiveCarrierMethod( string $fullyQualifiedClassname, array $methods ): array {
		/**
		 * Generated shipping method.
		 *
		 * @var BaseShippingMethod $fullyQualifiedClassname
		 */
		$carrierOptions = $this->carrierOptionsFactory->createByCarrierId( $fullyQualifiedClassname::CARRIER_ID );
		if ( $carrierOptions->isActive() ) {
			$methods[ $fullyQualifiedClassname::getShippingMethodId() ] = $fullyQualifiedClassname;
		}

		return $methods;
	}

	/**
	 * Sort classnames by method_title property.
	 *
	 * @param array $methods Methods to sort.
	 *
	 * @return array
	 */
	public function sortMethods( array $methods ): array {
		uasort(
			$methods,
			function ( $classA, $classB ) {
				$objectA = new $classA();
				$objectB = new $classB();

				return strcmp( $objectA->method_title, $objectB->method_title );
			}
		);

		return $methods;
	}

}