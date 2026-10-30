<?php

declare( strict_types=1 );

namespace Packetery\Module\Shipping;

use Packetery\Core\Entity\Carrier;
use Packetery\Module\Carrier\CarDeliveryConfig;
use Packetery\Module\Carrier\CarrierOptionsFactory;
use Packetery\Module\Carrier\EntityRepository;
use Packetery\Module\ContextResolver;
use Packetery\Module\ShippingMethod;
use Packetery\Module\ShippingZoneRepository;
use WC_Abstract_Order;

class ShippingProvider {
	/**
	 * @var array<int, array<string, string|object>>
	 */
	private static $sortedMethodsCache = [];

	/**
	 * @var CarDeliveryConfig
	 */
	private $carDeliveryConfig;

	/**
	 * @var ContextResolver
	 */
	private $contextResolver;

	/**
	 * @var ShippingZoneRepository
	 */
	private $shippingZoneRepository;

	/**
	 * @var EntityRepository
	 */
	private $carrierRepository;

	/**
	 * @var CarrierOptionsFactory
	 */
	private $carrierOptionsFactory;

	/**
	 * @var InstanceSettingsPrimer
	 */
	private $instanceSettingsPrimer;

	public function __construct(
		CarDeliveryConfig $carDeliveryConfig,
		ContextResolver $contextResolver,
		ShippingZoneRepository $shippingZoneRepository,
		EntityRepository $carrierRepository,
		CarrierOptionsFactory $carrierOptionsFactory,
		InstanceSettingsPrimer $instanceSettingsPrimer
	) {
		$this->carDeliveryConfig      = $carDeliveryConfig;
		$this->contextResolver        = $contextResolver;
		$this->shippingZoneRepository = $shippingZoneRepository;
		$this->carrierRepository      = $carrierRepository;
		$this->carrierOptionsFactory  = $carrierOptionsFactory;
		$this->instanceSettingsPrimer = $instanceSettingsPrimer;
	}

	/**
	 * Loads generated shipping methods, including split ones when split is off.
	 * Used in CLI generator.
	 */
	public static function loadAllClasses(): void {
		$generatedClassesPath = __DIR__ . '/Generated';
		foreach ( scandir( $generatedClassesPath ) as $filename ) {
			if ( preg_match( '/\.php$/', $filename ) ) {
				require_once $generatedClassesPath . '/' . $filename;
			}
		}
	}

	/**
	 * Loads generated shipping methods.
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
			require_once $generatedClassesPath . '/' . $filename;
		}
	}

	/**
	 * Gets all full classnames of generated shipping methods.
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

	public static function wcOrderHasOurMethod( WC_Abstract_Order $wcOrder ): bool {
		foreach ( $wcOrder->get_shipping_methods() as $shippingMethod ) {
			if ( self::isPacketaMethod( $shippingMethod->get_method_id() ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks if provided shipping method id belongs to one of Packeta methods.
	 */
	public static function isPacketaMethod( string $methodId ): bool {
		return (
			$methodId === ShippingMethod::PACKETERY_METHOD_ID ||
			strpos( $methodId, BaseShippingMethod::PACKETA_METHOD_PREFIX ) === 0
		);
	}

	/**
	 * Loads shipping methods, uses different approach for zone setting page.
	 *
	 * @param array<string, string|object> $methods Previous state.
	 *
	 * @return array<string, string|object>
	 */
	private function addMethods( array $methods ): array {
		$zoneId = $this->contextResolver->getShippingZoneId();
		if ( $zoneId === null ) {
			return $this->addAllMethods( $methods );
		}

		$allowedCountries = $this->shippingZoneRepository->getCountryCodesForShippingZone( $zoneId );
		if ( $allowedCountries === [] ) {
			return $this->addAllMethods( $methods );
		}

		foreach ( $allowedCountries as $countryCode ) {
			$carriers = $this->carrierRepository->getByCountryIncludingNonFeed( $countryCode, false );
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
	 * @param array<string, string|object> $methods Array.
	 *
	 * @return array<string, string|object>
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
	 * @param string                       $fullyQualifiedClassname Fully qualified classname.
	 * @param array<string, string|object> $methods                 Methods.
	 *
	 * @return array<string, string|object>
	 */
	private function addActiveCarrierMethod( string $fullyQualifiedClassname, array $methods ): array {
		/**
		 * Generated shipping method.
		 *
		 * @var BaseShippingMethod $fullyQualifiedClassname
		 */
		// @phpstan-ignore-next-line
		$carrierOptions = $this->carrierOptionsFactory->createByCarrierId( $fullyQualifiedClassname::CARRIER_ID );
		if ( $carrierOptions->isActive() ) {
			$methods[ $fullyQualifiedClassname::getShippingMethodId() ] = $fullyQualifiedClassname;
		}

		return $methods;
	}

	/**
	 * Sorts methods by method title. Each title is read once, before the sort.
	 * The methods include foreign ones, and a value can also be an instance.
	 *
	 * @param array<string, string|object> $methods Methods to sort.
	 *
	 * @return array<string, string|object>
	 */
	private function sortMethods( array $methods ): array {
		$titles = [];
		foreach ( $methods as $methodId => $method ) {
			$titles[ $methodId ] = $this->getTitleForSorting( $method );
		}

		uksort(
			$methods,
			function ( $methodIdA, $methodIdB ) use ( $titles ): int {
				return strcmp( $titles[ $methodIdA ], $titles[ $methodIdB ] );
			}
		);

		return $methods;
	}

	/**
	 * The title of a Packeta method comes from the carrier cache, so no instance is needed.
	 *
	 * @param string|object $method Class name or instance of a shipping method.
	 */
	private function getTitleForSorting( $method ): string {
		if ( is_object( $method ) ) {
			// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
			return (string) $method->method_title;
		}

		if ( is_subclass_of( $method, BaseShippingMethod::class ) ) {
			return $method::getMethodTitle( $this->carrierRepository );
		}

		$instance = new $method();

		// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
		return (string) $instance->method_title;
	}

	/**
	 * WooCommerce runs this filter in WC_Shipping_Zone::get_shipping_methods() before it creates the zone methods,
	 * so the instance settings are primed before the method constructors read them.
	 *
	 * @param array<string, string|object> $originalMethods
	 *
	 * @return array<string, string|object>
	 */
	public function getSortedCachedMethods( array $originalMethods ): array {
		$areClassesLoaded = in_array( BaseShippingMethod::class, get_declared_classes(), true );
		if ( $areClassesLoaded === false ) {
			return $originalMethods;
		}

		$this->instanceSettingsPrimer->prime();

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		$cacheKey = crc32( serialize( $originalMethods ) );

		if ( ! isset( self::$sortedMethodsCache[ $cacheKey ] ) ) {
			$unsortedMethods                       = $this->addMethods( $originalMethods );
			self::$sortedMethodsCache[ $cacheKey ] = $this->sortMethods( $unsortedMethods );
		}

		return self::$sortedMethodsCache[ $cacheKey ];
	}
}
