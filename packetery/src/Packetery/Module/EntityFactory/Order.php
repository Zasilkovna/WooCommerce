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
use Packetery\Core\Entity\Size;
use Packetery\Module\Carrier\Repository;
use Packetery\Module\Options\Provider;
use Packetery\Module\Order as ModuleOrder;
use Packetery\Module\Address as ModuleAddress;
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
	 * @var Repository Carrier repository.
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
	 * @param Provider                 $optionsProvider   Options Provider.
	 * @param Repository               $carrierRepository Carrier repository.
	 * @param ModuleAddress\Repository $addressRepository Address repository.
	 */
	public function __construct( Provider $optionsProvider, Repository $carrierRepository, ModuleAddress\Repository $addressRepository ) {
		$this->optionsProvider   = $optionsProvider;
		$this->carrierRepository = $carrierRepository;
		$this->addressRepository = $addressRepository;
	}

	/**
	 * Creates common order entity from WC_Order.
	 *
	 * @param WC_Order $order WC_Order.
	 *
	 * @return Entity\Order
	 */
	public function create( WC_Order $order ): Entity\Order {
		$orderData   = $order->get_data();
		$orderId     = (string) $orderData['id'];
		$contactInfo = ( $order->has_shipping_address() ? $orderData['shipping'] : $orderData['billing'] );
		// Type cast of $orderTotalPrice is needed, PHPDoc is wrong.
		$orderValue  = (float) $order->get_total( 'raw' );
		$moduleOrder = new ModuleOrder\Entity( $order );

		$orderEntity = new Entity\Order(
			$orderId,
			$contactInfo['first_name'],
			$contactInfo['last_name'],
			$orderValue,
			$moduleOrder->getWeight(),
			$this->optionsProvider->get_sender(),
			$moduleOrder->getCarrierId()
		);

		$orderEntity->setPacketId( $moduleOrder->getPacketId() );
		$orderEntity->setIsExported( $moduleOrder->isExported() );
		$orderEntity->setAdultContent( $moduleOrder->containsAdultContent() );

		if ( ! $moduleOrder->isHomeDelivery() ) {
			$pickupPoint = new Entity\PickupPoint(
				$moduleOrder->getPointId(),
				$moduleOrder->getPointType(),
				$moduleOrder->getPointName(),
				$moduleOrder->getPointCity(),
				$moduleOrder->getPointZip(),
				$moduleOrder->getPointStreet(),
				$moduleOrder->getPointUrl(),
				$moduleOrder->getPointCarrierId()
			);
			$orderEntity->setPickupPoint( $pickupPoint );
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
			$orderEntity->setCod( $orderValue );
		}
		$size = new Size( $moduleOrder->getLength(), $moduleOrder->getWidth(), $moduleOrder->getHeight() );
		$orderEntity->setSize( $size );

		if ( $moduleOrder->isExternalCarrier() ) {
			$carrier = $this->carrierRepository->getById( (int) $orderEntity->getCarrierId() );
			$orderEntity->setCarrier( $carrier );
		}

		return $orderEntity;
	}

}
