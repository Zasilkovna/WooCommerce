<?php
/**
 * Class WidgetOptionsBuilder
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module;

use Packetery\Core\Entity;
use Packetery\Core\Entity\Order;
use Packetery\Module\Api\Internal\OrderRouter;
use Packetery\Module\Carrier\CarDeliveryConfig;
use Packetery\Module\Carrier\EntityRepository;
use Packetery\Module\Carrier\OptionPrefixer;
use Packetery\Module\Carrier\PacketaPickupPointsConfig;
use Packetery\Module\Options\FeatureFlagManager;
use Packetery\Module\Options\Provider;
use Packetery\Module\Order\Attribute;
use WC_DateTime;

/**
 * Class WidgetOptionsBuilder
 *
 * @package Packetery
 */
class WidgetOptionsBuilder {

	/**
	 * Internal pickup points config.
	 *
	 * @var PacketaPickupPointsConfig
	 */
	private $pickupPointsConfig;

	/**
	 * Feature flag.
	 *
	 * @var FeatureFlagManager
	 */
	private $featureFlag;

	private $carDeliveryConfig;

	private $options_provider;

	private $apiRouter;

	private $carrierEntityRepository;

	/**
	 * WidgetOptionsBuilder constructor.
	 *
	 * @param PacketaPickupPointsConfig $pickupPointsConfig Internal pickup points config.
	 * @param FeatureFlagManager        $featureFlag        Feature flag.
	 */
	public function __construct(
		PacketaPickupPointsConfig $pickupPointsConfig,
		FeatureFlagManager $featureFlag,
		CarDeliveryConfig $carDeliveryConfig,
		Provider $options_provider,
		OrderRouter $apiRouter,
		EntityRepository $carrierEntityRepository
	) {
		$this->pickupPointsConfig      = $pickupPointsConfig;
		$this->featureFlag             = $featureFlag;
		$this->carDeliveryConfig       = $carDeliveryConfig;
		$this->options_provider        = $options_provider;
		$this->apiRouter               = $apiRouter;
		$this->carrierEntityRepository = $carrierEntityRepository;
	}

	/**
	 * Gets widget vendors param.
	 *
	 * @param string     $carrierId    Carrier id.
	 * @param string     $country      Country.
	 * @param array|null $vendorGroups Vendor groups.
	 *
	 * @return array|null
	 */
	private function getWidgetVendorsParam( string $carrierId, string $country, ?array $vendorGroups ): ?array {
		if ( is_numeric( $carrierId ) ) {
			return [
				[
					'carrierId' => $carrierId,
					'selected'  => true,
				],
			];
		}

		$vendorCarriers = $this->pickupPointsConfig->getVendorCarriers();
		if ( ! empty( $vendorCarriers[ $carrierId ] ) ) {
			$vendorGroups = [ $vendorCarriers[ $carrierId ]->getGroup() ];
		}

		if ( empty( $vendorGroups ) ) {
			return null;
		}

		$vendorsParam = [];
		foreach ( $vendorGroups as $code ) {
			$groupSettings = [
				'selected' => true,
				'country'  => $country,
			];
			if ( Entity\Carrier::VENDOR_GROUP_ZPOINT !== $code ) {
				$groupSettings['group'] = $code;
			}
			$vendorsParam[] = $groupSettings;
		}

		return $vendorsParam;
	}

	/**
	 * Gets widget carriers param.
	 *
	 * @param bool   $isPickupPoints Is context pickup point related.
	 * @param string $carrierId      Carrier id.
	 *
	 * @return string|null
	 */
	private function getCarriersParam( bool $isPickupPoints, string $carrierId ): ?string {
		if ( $isPickupPoints ) {
			return ( is_numeric( $carrierId ) ? $carrierId : Entity\Carrier::INTERNAL_PICKUP_POINTS_ID );
		}

		return null;
	}

	/**
	 * Gets carrier configuration for widgets in frontend.
	 *
	 * @param Entity\Carrier $carrier         Carrier configuration.
	 * @param string         $optionId        Option id.
	 *
	 * @return array
	 */
	public function getCarrierForCheckout( Entity\Carrier $carrier, string $optionId ): array {
		$carrierConfigForWidget = [
			'id'               => $carrier->getId(),
			'is_pickup_points' => (int) $carrier->hasPickupPoints(),
		];

		$carrierOption = get_option( $optionId );
		if ( $carrier->hasPickupPoints() ) {
			if ( $this->featureFlag->isSplitActive() ) {
				$carrierConfigForWidget['vendors'] = $this->getWidgetVendorsParam(
					$carrier->getId(),
					$carrier->getCountry(),
					( ( $carrierOption && isset( $carrierOption['vendor_groups'] ) ) ? $carrierOption['vendor_groups'] : null )
				);
			} else {
				$carrierConfigForWidget['carriers'] = $this->getCarriersParam( true, $carrier->getId() );
			}
		}

		if ( ! $carrier->hasPickupPoints() ) {
			$addressValidation = 'none';
			if ( $carrierOption && in_array( $carrier->getCountry(), Entity\Carrier::ADDRESS_VALIDATION_COUNTRIES, true ) ) {
				$addressValidation = ( $carrierOption['address_validation'] ?? $addressValidation );
			}

			$carrierConfigForWidget['address_validation'] = $addressValidation;
		}

		return $carrierConfigForWidget;
	}

	/**
	 * Creates pickup point widget options in backend.
	 *
	 * @param Order $order Order.
	 *
	 * @return array|null
	 */
	public function createPickupPointForAdmin( Order $order ): array {
		$widgetOptions = [
			'country'     => $order->getShippingCountry(),
			'language'    => substr( get_user_locale(), 0, 2 ),
			'appIdentity' => Plugin::getAppIdentity(),
			'weight'      => $order->getFinalWeight(),
		];

		if ( $this->featureFlag->isSplitActive() ) {
			// In backend, we want all pickup points in that country for packeta carrier.
			if ( $order->getCarrier()->getId() !== Entity\Carrier::INTERNAL_PICKUP_POINTS_ID ) {
				$widgetOptions['vendors'] = $this->getWidgetVendorsParam(
					$order->getCarrier()->getId(),
					$order->getShippingCountry(),
					null
				);
			}
		} else {
			$widgetOptions['carriers'] = $this->getCarriersParam( $order->isPickupPointDelivery(), $order->getCarrier()->getId() );
		}

		if ( $order->containsAdultContent() ) {
			$widgetOptions += [ 'livePickupPoint' => true ];
		}

		return $widgetOptions;
	}

	/**
	 * Creates address validation widget options in backend.
	 *
	 * @param Order $order Order.
	 *
	 * @return array
	 */
	public function createAddressForAdmin( Order $order ): array {
		/**
		 * Delivery address is always present in this case.
		 *
		 * @var Entity\Address $deliveryAddress
		 */
		$deliveryAddress = $order->getDeliveryAddress();
		$widgetOptions   = [
			'country'     => $order->getShippingCountry(),
			'language'    => substr( get_user_locale(), 0, 2 ),
			'layout'      => 'hd',
			'appIdentity' => Plugin::getAppIdentity(),
			'street'      => $deliveryAddress->getStreet(),
			'city'        => $deliveryAddress->getCity(),
			'postcode'    => $deliveryAddress->getZip(),
		];

		if ( $deliveryAddress->getHouseNumber() ) {
			$widgetOptions += [ 'houseNumber' => $deliveryAddress->getHouseNumber() ];
		}

		if ( $deliveryAddress->getCounty() ) {
			$widgetOptions += [ 'county' => $deliveryAddress->getCounty() ];
		}

		if ( is_numeric( $order->getCarrier()->getId() ) ) {
			$widgetOptions += [ 'carrierId' => $order->getCarrier()->getId() ];
		}

		return $widgetOptions;
	}

	/**
	 * Creates settings for eshop order details script.
	 *
	 * @param Order       $order            Order.
	 * @param WC_Datetime $orderDateCreated Order date created.
	 */
	public function createCarDeliverySettings( Order $order, WC_DateTime $orderDateCreated ): array {
		return [
			/**
			 * Filter widget language in checkout.
			 *
			 * @since 1.4.2
			 */
			'language'                    => (string) apply_filters( 'packeta_widget_language', substr( get_locale(), 0, 2 ) ),
			'isCarDeliverySampleEnabled'  => $this->carDeliveryConfig->isSampleEnabled(),
			'carDeliveryAttrs'            => Attribute::$carDeliveryAttrs,
			'orderId'                     => $order->getNumber(),
			'expeditionDay'               => $this->calculateExpeditionDay( $order, $orderDateCreated ),
			'packeteryApiKey'             => $this->options_provider->get_api_key(),
			'updateCarDeliveryAddressUrl' => $this->apiRouter->getSaveDeliveryAddressUrl(),
			'isSubmittedToPacketa'        => $order->isExported(),
			'appIdentity'                 => Plugin::getAppIdentity(),
			'nonce'                       => wp_create_nonce( 'wp_rest' ),
			'translations'                => [
				'chooseAddress' => __( 'Choose delivery address', 'packeta' ),
			],
		];
	}

	/**
	 * Calculates and returns Expedition Day
	 *
     * @param ?string      $chosenShippingMethod Chosen shipping method.
	 * @param ?Order       $order                Order.
	 * @param ?WC_DateTime $orderDateCreated     Order date created.
	 * @return string
	 */
	public function calculateExpeditionDay( Order $order = null, WC_DateTime $orderDateCreated = null, string $chosenShippingMethod = null ): ?string {
		if ( null === $order ) {
//			$carrierId            = OptionPrefixer::removePrefix( $chosenShippingMethod );
			$carrierId            = OptionPrefixer::removePrefix( '25061' );
			$date                 = new WC_DateTime();
		} else {
			$carrierId = $order->getCarrier()->getId();
			if ( null === $orderDateCreated ) {
				return null;
			}
			$date = $orderDateCreated;
		}

		if ( false === $this->carrierEntityRepository->isCarDeliveryCarrier( $carrierId ) ) {
			return null;
		}

		$carrierOptions = Carrier\Options::createByOptionId( OptionPrefixer::getOptionId( $carrierId ) )->toArray();
		$processingDays = $carrierOptions['days_until_shipping'];
		$cutoffTime     = $carrierOptions['shipping_time_cut_off'];

		// Check if a cut-off time is provided and if the current time is after the cut-off time.
		if ( null !== $cutoffTime ) {
			$currentTime = $date->format( 'H:i' );
			if ( $currentTime > $cutoffTime ) {
				// If after cut-off time, move to the next day.
				$date->modify( '+1 day' );
			}
		}

		// Loop through each day to add processing days, skipping weekends.
		for ( $i = 0; $i < $processingDays; $i++ ) {
			// Add a day to the current date.
			$date->modify( '+1 day' );

			// Check if the current day is a weekend (Saturday or Sunday).
			if ( $date->format( 'N' ) >= 6 ) {
				// If it's a weekend, move to the next Monday.
				$date->modify( 'next Monday' );
			}
		}

		// Get the final expedition day.
		return $date->format( 'Y-m-d' );
	}
}
