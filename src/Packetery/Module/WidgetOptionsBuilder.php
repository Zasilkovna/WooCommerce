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
use Packetery\Module\Carrier\PacketaPickupPointsConfig;
use Packetery\Module\Options\FeatureFlagManager;
use Packetery\Module\Order\Repository;
use WC_Order;

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
	 * RateCalculator.
	 *
	 * @var RateCalculator
	 */
	private $rateCalculator;

	/**
	 * Order repository.
	 *
	 * @var Repository
	 */
	private $orderRepository;

	/**
	 * Feature flag.
	 *
	 * @var FeatureFlagManager
	 */
	private $featureFlag;

	/**
	 * WidgetOptionsBuilder constructor.
	 *
	 * @param PacketaPickupPointsConfig $pickupPointsConfig Internal pickup points config.
	 * @param RateCalculator            $rateCalculator     RateCalculator.
	 * @param Repository                $orderRepository    Order repository.
	 * @param FeatureFlagManager        $featureFlag        Feature flag.
	 */
	public function __construct(
		PacketaPickupPointsConfig $pickupPointsConfig,
		RateCalculator $rateCalculator,
		Repository $orderRepository,
		FeatureFlagManager $featureFlag
	) {
		$this->pickupPointsConfig = $pickupPointsConfig;
		$this->rateCalculator     = $rateCalculator;
		$this->orderRepository    = $orderRepository;
		$this->featureFlag        = $featureFlag;
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
	 * Gets carrier configuration for widgets in frontend.
	 *
	 * @param Entity\Carrier $carrier         Carrier configuration.
	 * @param float|null     $defaultPrice    Default price.
	 * @param string         $optionId        Option id.
	 *
	 * @return array
	 */
	public function getCarrierForCheckout( Entity\Carrier $carrier, ?float $defaultPrice, string $optionId ): array {
		$carrierConfigForWidget = [
			'id'               => $carrier->getId(),
			'is_pickup_points' => (int) $carrier->hasPickupPoints(),
			'defaultPrice'     => $defaultPrice,
			'defaultCurrency'  => $carrier->getCurrency(),
		];

		$carrierOption = get_option( $optionId );
		if ( $carrier->hasPickupPoints() && $this->featureFlag->isSplitActive() ) {
			$carrierConfigForWidget['vendors'] = $this->getWidgetVendorsParam(
				(string) $carrier->getId(),
				$carrier->getCountry(),
				( ( $carrierOption && isset( $carrierOption['vendor_groups'] ) ) ? $carrierOption['vendor_groups'] : null )
			);
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
		if ( $order->isExternalCarrier() ) {
			$carrierId = $order->getCarrierId();
		} else {
			$carrierId = $this->pickupPointsConfig->getCompoundCarrierIdByCountry( $order->getShippingCountry() );
		}

		$defaultPrice = null;
		if ( null !== $carrierId ) {
			/**
			 * Cannot be null due to the conditions in the caller method.
			 *
			 * @var WC_Order $wcOrder
			 */
			$wcOrder      = $this->orderRepository->getWcOrderById( (int) $order->getNumber() );
			$defaultPrice = $this->rateCalculator->getShippingRateCost(
				Carrier\Options::createByCarrierId( $carrierId ),
				$wcOrder->get_total() - $wcOrder->get_shipping_total(),
				$order->getWeight(),
				$this->rateCalculator->isFreeShippingCouponApplied( $wcOrder )
			);
		}

		$widgetOptions = [
			'country'      => $order->getShippingCountry(),
			'language'     => substr( get_user_locale(), 0, 2 ),
			'appIdentity'  => Plugin::getAppIdentity(),
			'weight'       => $order->getFinalWeight(),
			'defaultPrice' => $defaultPrice,
		];

		// TODO: update later when carrier will not be nullable.
		$orderCarrier = $order->getCarrier();
		if ( $orderCarrier ) {
			$widgetOptions['defaultCurrency'] = $orderCarrier->getCurrency();
		}

		// In backend, we want all pickup points in that country for packeta carrier.
		if (
			$order->getCarrierId() !== Entity\Carrier::INTERNAL_PICKUP_POINTS_ID &&
			$this->featureFlag->isSplitActive()
		) {
			$widgetOptions['vendors'] = $this->getWidgetVendorsParam(
				$order->getCarrierId(),
				$order->getShippingCountry(),
				null
			);
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

		// TODO: Redo in carrier refactor.
		if ( $order->getCarrier() && is_numeric( $order->getCarrier()->getId() ) ) {
			$widgetOptions += [ 'carrierId' => $order->getCarrier()->getId() ];
		}

		return $widgetOptions;
	}

}
