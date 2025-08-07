<?php

declare( strict_types=1 );

namespace Packetery\Module\Shipping;

use Packetery\Core\Entity\Carrier;
use Packetery\Module\Carrier\CarDeliveryConfig;
use Packetery\Module\Carrier\CarrierOptionsFactory;
use Packetery\Module\Carrier\EntityRepository;
use Packetery\Module\Carrier\PacketaPickupPointsConfig;
use Packetery\Module\ContextResolver;
use Packetery\Module\Options\FlagManager\FeatureFlagProvider;
use Packetery\Module\ShippingMethod;
use Packetery\Module\ShippingZoneRepository;
use WC_Order;

class ShippingProvider {

	/**
	 * Cache pro dopravce
	 *
	 * @var array<string, Carrier|null>
	 */
	private static array $carrierCache = [];

	/**
	 * @var FeatureFlagProvider
	 */
	private $featureFlagProvider;

	/**
	 * @var PacketaPickupPointsConfig
	 */
	private $pickupPointConfig;

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

	public function __construct(
		FeatureFlagProvider $featureFlagProvider,
		PacketaPickupPointsConfig $pickupPointConfig,
		CarDeliveryConfig $carDeliveryConfig,
		ContextResolver $contextResolver,
		ShippingZoneRepository $shippingZoneRepository,
		EntityRepository $carrierRepository,
		CarrierOptionsFactory $carrierOptionsFactory
	) {
		$this->featureFlagProvider    = $featureFlagProvider;
		$this->pickupPointConfig      = $pickupPointConfig;
		$this->carDeliveryConfig      = $carDeliveryConfig;
		$this->contextResolver        = $contextResolver;
		$this->shippingZoneRepository = $shippingZoneRepository;
		$this->carrierRepository      = $carrierRepository;
		$this->carrierOptionsFactory  = $carrierOptionsFactory;
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
			if ( $this->featureFlagProvider->isSplitActive() === false ) {
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
	 */
	public static function wcOrderHasOurMethod( WC_Order $wcOrder ): bool {
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
	 * @param array<string, string> $methods Previous state.
	 *
	 * @return array<string, string>
	 */
	public function addMethods( array $methods ): array {
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
	 * @param array<string, string> $methods Array.
	 *
	 * @return array<string, string>
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
	 * @param string                $fullyQualifiedClassname Fully qualified classname.
	 * @param array<string, string> $methods                 Methods.
	 *
	 * @return array<string, string>
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
	 * Sort classnames by method_title property.
	 *
	 * @param array<string, string> $methods Methods to sort.
	 *
	 * @return array<string, string>
	 */
	public function sortMethods(array $methods): array {
		// Získání všech carrier ID z metod
		$carrierIds = [];
		foreach ($methods as $fullyQualifiedClassname => $methodTitle) {
			if (defined("$fullyQualifiedClassname::CARRIER_ID")) {
				$carrierIds[] = $fullyQualifiedClassname::CARRIER_ID;
			}
		}

		// Předběžné načtení všech dopravců najednou
		if (!empty($carrierIds)) {
			$this->preloadCarriers($carrierIds);
		}

		uasort(
			$methods,
			function ($fullyQualifiedClassnameA, $fullyQualifiedClassnameB) {
				$methodTitleA = null;
				$methodTitleB = null;

				// Použití cache místo přímého volání repositáře
				if (defined("$fullyQualifiedClassnameA::CARRIER_ID")) {
					$carrier = $this->getCachedCarrier((int)$fullyQualifiedClassnameA::CARRIER_ID);
					if ($carrier !== null) {
						$methodTitleA = $carrier->getName();
					}
				}

				if (defined("$fullyQualifiedClassnameB::CARRIER_ID")) {
					$carrier = $this->getCachedCarrier((int)$fullyQualifiedClassnameB::CARRIER_ID);
					if ($carrier !== null) {
						$methodTitleB = $carrier->getName();
					}
				}

				if ($methodTitleA === null || $methodTitleB === null) {
					return 0;
				}

				return strcasecmp($methodTitleA, $methodTitleB);
			}
		);

		return $methods;
	}

	/**
	 * Předběžně načte dopravce do cache
	 *
	 * @param string[] $carrierIds ID dopravců
	 * @return void
	 */
	private function preloadCarriers(array $carrierIds): void {
		// Filtrujeme pouze ID, která ještě nemáme v cache
		$missingIds = array_filter($carrierIds, function($id) {
			return !isset(self::$carrierCache[$id]);
		});

		if (empty($missingIds)) {
			return;
		}

		$inClause = $this->carrierRepository->getWpdbAdapter()->prepareInClause($missingIds);
		$query = sprintf(
			'SELECT * FROM `%s` WHERE `id` IN (%s)',
			$this->carrierRepository->getWpdbAdapter()->packeteryCarrier,
			$inClause
		);

		$results = $this->carrierRepository->getWpdbAdapter()->get_results($query, \ARRAY_A);

		foreach ($results as $carrierData) {
			$carrier = $this->carrierRepository->createEntityFromDbResult($carrierData);
			self::$carrierCache[$carrier->getId()] = $carrier;
		}
	}

	/**
	 * Získá dopravce z cache nebo z databáze
	 *
	 * @param string $carrierId ID dopravce
	 * @return Carrier|null
	 */
	private function getCachedCarrier(int $carrierId): ?Carrier {
		if (!isset(self::$carrierCache[$carrierId])) {
			self::$carrierCache[$carrierId] = $this->carrierRepository->getById($carrierId);
		}

		return self::$carrierCache[$carrierId];
	}

	/**
	 * Vyčistí cache dopravců
	 *
	 * @return void
	 */
	public static function clearCarrierCache(): void {
		self::$carrierCache = [];
	}
}
