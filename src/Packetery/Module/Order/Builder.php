<?php
/**
 * Class Builder
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use DateTimeImmutable;
use DateTimeZone;
use Packetery\Core\CoreHelper;
use Packetery\Core\Entity;
use Packetery\Core\Entity\Address;
use Packetery\Core\Entity\Order;
use Packetery\Core\Entity\PickupPoint;
use Packetery\Core\Entity\Size;
use Packetery\Module\Carrier;
use Packetery\Module\Carrier\PacketaPickupPointsConfig;
use Packetery\Module\CustomsDeclaration;
use Packetery\Module\Exception\InvalidCarrierException;
use Packetery\Module\ModuleHelper;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Payment\PaymentHelper;
use Packetery\Module\Product;
use Packetery\Module\WeightCalculator;
use stdClass;
use WC_Order;
use WC_Order_Item_Product;
use WC_Product;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
/**
 * Class Builder
 *
 * @package Packetery
 */
class Builder {

	/**
	 * Options provider.
	 *
	 * @var OptionsProvider Options provider.
	 */
	private $optionsProvider;

	/**
	 * Weight calculator.
	 *
	 * @var WeightCalculator
	 */
	private $calculator;

	/**
	 * Customs declaration repository.
	 *
	 * @var CustomsDeclaration\Repository
	 */
	private $customsDeclarationRepository;

	/**
	 * Internal pickup points config.
	 *
	 * @var PacketaPickupPointsConfig
	 */
	private $pickupPointsConfig;

	/**
	 * CoreHelper.
	 *
	 * @var CoreHelper
	 */
	private $coreHelper;

	/**
	 * Payment helper.
	 *
	 * @var PaymentHelper
	 */
	private $paymentHelper;

	/**
	 * Carrier repository.
	 *
	 * @var Carrier\EntityRepository
	 */
	private $carrierRepository;

	/**
	 * Builder constructor.
	 *
	 * @param OptionsProvider               $optionsProvider              Options Provider.
	 * @param WeightCalculator              $calculator                   Weight calculator.
	 * @param CustomsDeclaration\Repository $customsDeclarationRepository Customs declaration repository.
	 * @param PacketaPickupPointsConfig     $pickupPointsConfig           Internal pickup points config.
	 * @param CoreHelper                    $coreHelper                   CoreHelper.
	 * @param PaymentHelper                 $paymentHelper                Payment helper.
	 * @param Carrier\EntityRepository      $carrierRepository            Carrier repository.
	 */
	public function __construct(
		OptionsProvider $optionsProvider,
		WeightCalculator $calculator,
		CustomsDeclaration\Repository $customsDeclarationRepository,
		PacketaPickupPointsConfig $pickupPointsConfig,
		CoreHelper $coreHelper,
		PaymentHelper $paymentHelper,
		Carrier\EntityRepository $carrierRepository
	) {
		$this->optionsProvider              = $optionsProvider;
		$this->calculator                   = $calculator;
		$this->customsDeclarationRepository = $customsDeclarationRepository;
		$this->pickupPointsConfig           = $pickupPointsConfig;
		$this->coreHelper                   = $coreHelper;
		$this->paymentHelper                = $paymentHelper;
		$this->carrierRepository            = $carrierRepository;
	}

	/**
	 * Creates order entity from WC_Order and plugin data.
	 *
	 * @param WC_Order $wcOrder WC_Order.
	 * @param stdClass $result Db result, plugin specific data.
	 *
	 * @return Entity\Order
	 * @throws InvalidCarrierException In case Carrier entity could not be created.
	 */
	public function build( WC_Order $wcOrder, stdClass $result ): Entity\Order {
		$country = $this->getCountryFromWcOrder( $wcOrder );
		$carrier = $this->getCarrier( $result->carrier_id, $country );
		$size    = new Size(
			$this->parseFloat( $result->length ),
			$this->parseFloat( $result->width ),
			$this->parseFloat( $result->height )
		);

		$order       = Order::createOrderFromWcOrder(
			$result->id,
			$carrier,
			$result->packet_id,
			$this->coreHelper->getTrackingUrl( $result->packet_id ),
			$result->packet_claim_id,
			$this->coreHelper->getTrackingUrl( $result->packet_claim_id ),
			$result->packet_claim_password,
			(bool) $result->is_exported,
			(bool) $result->is_label_printed,
			$size,
			$result->packet_status,
			$this->coreHelper->getDateTimeFromString( $result->stored_until ),
			(bool) $result->address_validated,
			$this->parseBool( $result->adult_content ),
			$this->parseFloat( $result->value ),
			$this->parseFloat( $result->cod ),
			$this->coreHelper->getDateTimeFromString( $result->deliver_on ),
			$this->optionsProvider->get_sender()
		);
		$orderWeight = $this->parseFloat( $result->weight );
		if ( $orderWeight !== null ) {
			$order->setWeight( $orderWeight );
		}

		// $order->setCarrierNumber( $result->carrier_number );
		$order->setLastApiErrorMessage( $result->api_error_message );
		$order->setLastApiErrorDateTime(
			( $result->api_error_date === null )
				? null
				: DateTimeImmutable::createFromFormat(
					CoreHelper::MYSQL_DATETIME_FORMAT,
					$result->api_error_date,
					new DateTimeZone( 'UTC' )
				)->setTimezone( wp_timezone() )
		);

		$this->setAddressDetails( $order, $result );

		if ( $result->car_delivery_id ) {
			$order->setCarDeliveryId( $result->car_delivery_id );
		}

		if ( $result->point_id !== null ) {
			$pickUpPoint = new PickupPoint(
				$result->point_id,
				$result->point_name,
				$result->point_city,
				$result->point_zip,
				$result->point_street,
				$result->point_url
			);

			$order->setPickupPoint( $pickUpPoint );
		}

		$order->setCalculatedWeight( $this->calculator->calculateOrderWeight( $wcOrder ) );

		if ( $order->containsAdultContent() === null ) {
			$order->setAdultContent( $this->containsAdultContent( $wcOrder ) );
		}

		$order->setShippingCountry( ModuleHelper::getWcOrderCountry( $wcOrder ) );

		$orderData   = $wcOrder->get_data();
		$contactInfo = ( $wcOrder->has_shipping_address() ? $orderData['shipping'] : $orderData['billing'] );

		$order->setName( $contactInfo['first_name'] );
		$order->setSurname( $contactInfo['last_name'] );
		$order->setEshop( $this->optionsProvider->get_sender() );

		if ( $order->getValue() === null ) {
			$order->setValue( (float) $wcOrder->get_total( 'raw' ) );
		}

		$address = $order->getDeliveryAddress();
		if ( $address === null ) {
			$order->setAddressValidated( false );
			$address = new Address( $contactInfo['address_1'], $contactInfo['city'], $contactInfo['postcode'] );
		}

		$order->setDeliveryAddress( $address );
		$order->setCustomNumber( $wcOrder->get_order_number() );

		// Shipping address phone is optional.
		$order->setPhone( $orderData['billing']['phone'] );
		if ( isset( $contactInfo['phone'] ) && $contactInfo['phone'] !== '' ) {
			$order->setPhone( $contactInfo['phone'] );
		}
		// Additional address information.
		if ( isset( $contactInfo['address_2'] ) && $contactInfo['address_2'] !== '' ) {
			$order->setNote( $contactInfo['address_2'] );
		}

		$order->setEmail( $orderData['billing']['email'] );
		$hasCodPaymentMethod = $this->paymentHelper->isCodPaymentMethod( $orderData['payment_method'] );
		if ( $hasCodPaymentMethod && $order->getCod() === null ) {
			$order->setCod( $order->getValue() );
		}

		if ( ! $hasCodPaymentMethod ) {
			$order->setCod( null );
		}

		//$order->setSize( $order->getSize() );
		$order->setCurrency( $wcOrder->get_currency() );

		$order->setCustomsDeclaration( $this->customsDeclarationRepository->getByOrderNumber( $order->getNumber() ) );

		return $order;
	}

	/**
	 * Finds out if adult content is present.
	 *
	 * @param WC_Order $wcOrder WC Order.
	 *
	 * @return bool
	 */
	private function containsAdultContent( WC_Order $wcOrder ): bool {
		foreach ( $wcOrder->get_items() as $item ) {
			if ( $item instanceof WC_Order_Item_Product ) {
				$product = $item->get_product();
				if ( $product instanceof WC_Product ) {
					$productEntity = new Product\Entity( $product );
					if ( $productEntity->isPhysical() && $productEntity->isAgeVerification18PlusRequired() ) {
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Parses string value as float.
	 *
	 * @param string|float|null $value Value.
	 *
	 * @return float|null
	 */
	private function parseFloat( $value ): ?float {
		if ( $value === null || $value === '' ) {
			return null;
		}

		return (float) $value;
	}

	/**
	 * Parses string value as float.
	 *
	 * @param string|int|null $value Value.
	 *
	 * @return bool|null
	 */
	private function parseBool( $value ): ?bool {
		if ( $value === null || $value === '' ) {
			return null;
		}

		return (bool) $value;
	}

	private function getCountryFromWcOrder( WC_Order $wcOrder ): string {
		$country = ModuleHelper::getWcOrderCountry( $wcOrder );
		if ( $country === '' ) {
			throw new InvalidCarrierException( __( 'Please set the country of the delivery address first.', 'packeta' ) );
		}

		return $country;
	}

	private function getCarrier( string $carrierId, string $country ): Entity\Carrier {
		$carrierId = $this->pickupPointsConfig->getFixedCarrierId( $carrierId, $country );
		$carrier   = $this->carrierRepository->getAnyById( $carrierId );
		if ( $carrier === null ) {
			throw new InvalidCarrierException(
				sprintf( __( 'Order carrier is invalid (%s). Please contact Packeta support.', 'packeta' ), $carrierId )
			);
		}

		return $carrier;
	}

	private function setAddressDetails( Order $order, stdClass $result ): void {
		if ( $result->delivery_address ) {
			$decoded         = json_decode( $result->delivery_address, false );
			$deliveryAddress = new Address( $decoded->street, $decoded->city, $decoded->zip );
			$deliveryAddress->setHouseNumber( $decoded->houseNumber );
			$deliveryAddress->setLongitude( $decoded->longitude );
			$deliveryAddress->setLatitude( $decoded->latitude );
			$deliveryAddress->setCounty( $decoded->county );
			$order->setDeliveryAddress( $deliveryAddress );
		}
	}
}
