<?php
/**
 * Class Order
 *
 * @package Packetery\Module\EntityFactory
 */

declare( strict_types=1 );

namespace Packetery\Module\EntityFactory;

use Packetery\Core\Entity;
use Packetery\Core\Entity\Address;
use Packetery\Module\Carrier;
use Packetery\Module\Options\Provider;
use Packetery\Module\Address as ModuleAddress;
use Packetery\Core\Helper;
use WC_Order;

/**
 * Class Order
 *
 * @package Packetery\Module\EntityFactory
 */
class Order {

	/**
	 * Options provider.
	 *
	 * @var Provider Options provider.
	 */
	private $optionsProvider;

	/**
	 * Carrier repository.
	 *
	 * @var Carrier\Repository Carrier repository.
	 */
	private $carrierRepository;

	/**
	 * Address repository.
	 *
	 * @var ModuleAddress\Repository
	 */
	private $addressRepository;

	/**
	 * Order constructor.
	 *
	 * @param Provider                 $optionsProvider    Options Provider.
	 * @param Carrier\Repository       $carrierRepository  Carrier repository.
	 * @param ModuleAddress\Repository $addressRepository  Address repository.
	 */
	public function __construct(
		Provider $optionsProvider,
		Carrier\Repository $carrierRepository,
		ModuleAddress\Repository $addressRepository
	) {
		$this->optionsProvider   = $optionsProvider;
		$this->carrierRepository = $carrierRepository;
		$this->addressRepository = $addressRepository;
	}

	/**
	 * Creates common order entity from WC_Order.
	 *
	 * @param WC_Order     $order        WC_Order.
	 * @param Entity\Order $partialOrder Partial order.
	 *
	 * @return Entity\Order|null
	 */
	public function create( WC_Order $order, Entity\Order $partialOrder ): ?Entity\Order {
		$orderData   = $order->get_data();
		$orderId     = (string) $orderData['id'];
		$contactInfo = ( $order->has_shipping_address() ? $orderData['shipping'] : $orderData['billing'] );

		if ( null === $partialOrder->getCarrierId() ) {
			return null;
		}

		$orderEntity = new Entity\Order(
			$orderId,
			$contactInfo['first_name'],
			$contactInfo['last_name'],
			$partialOrder->getValue(),
			Helper::simplifyWeight( $partialOrder->getWeight() ),
			$this->optionsProvider->get_sender(),
			$partialOrder->getCarrierId()
		);

		$orderEntity->setPacketStatus( $partialOrder->getPacketStatus() );
		$orderEntity->setPacketId( $partialOrder->getPacketId() );
		$orderEntity->setIsExported( $partialOrder->isExported() );
		$orderEntity->setIsLabelPrinted( $partialOrder->isLabelPrinted() );
		$orderEntity->setCarrierNumber( $partialOrder->getCarrierNumber() );
		$orderEntity->setAdultContent( $partialOrder->containsAdultContent() );

		if ( $partialOrder->getPickupPoint() ) {
			$orderEntity->setPickupPoint( $partialOrder->getPickupPoint() );
		}

		$address = $this->addressRepository->getValidatedByOrderId( $order->get_id() );
		if ( null === $address ) {
			$address = new Address( $contactInfo['address_1'], $contactInfo['city'], $contactInfo['postcode'] );
		}

		$orderEntity->setDeliveryAddress( $address );

		// Shipping address phone is optional.
		$orderEntity->setPhone( $orderData['billing']['phone'] );
		if ( ! empty( $contactInfo['phone'] ) ) {
			$orderEntity->setPhone( $contactInfo['phone'] );
		}
		// Additional address information.
		if ( ! empty( $contactInfo['address_2'] ) ) {
			$orderEntity->setNote( $contactInfo['address_2'] );
		}

		$orderEntity->setEmail( $orderData['billing']['email'] );
		$codMethod = $this->optionsProvider->getCodPaymentMethod();
		if ( $orderData['payment_method'] === $codMethod ) {
			$orderEntity->setCod( $partialOrder->getValue() );
		}
		$orderEntity->setSize( $partialOrder->getSize() );

		if ( $orderEntity->isExternalCarrier() ) {
			$carrier = $this->carrierRepository->getById( (int) $orderEntity->getCarrierId() );
			$orderEntity->setCarrier( $carrier );
		}

		$orderEntity->setCurrency( $order->get_currency() );

		return $orderEntity;
	}
}
