<?php
/**
 * Class Order
 *
 * @package PacketeryModule\EntityFactory
 */

declare( strict_types=1 );

namespace PacketeryModule\EntityFactory;

use Packetery\Entity;
use Packetery\Entity\Address;
use Packetery\Entity\Size;
use PacketeryModule\Carrier\Repository;
use PacketeryModule\Options\Provider;
use PacketeryModule\Order as ModuleOrder;
use WC_Order;

/**
 * Class Order
 *
 * @package PacketeryModule\EntityFactory
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
	 * Order constructor.
	 *
	 * @param Provider   $optionsProvider Options Provider.
	 * @param Repository $carrierRepository Carrier repository.
	 */
	public function __construct( Provider $optionsProvider, Repository $carrierRepository ) {
		$this->optionsProvider   = $optionsProvider;
		$this->carrierRepository = $carrierRepository;
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
		// TODO: setAdultContent.

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

		$this->addHomeDeliveryDetails( $moduleOrder, $contactInfo, $orderEntity );

		// Shipping address phone is optional.
		$orderEntity->setPhone( $orderData['billing']['phone'] );
		if ( ! empty( $contactInfo['phone'] ) ) {
			$orderEntity->setPhone( $contactInfo['phone'] );
		}

		$orderEntity->setEmail( $orderData['billing']['email'] );
		$codMethod = $this->optionsProvider->getCodPaymentMethod();
		if ( $orderData['payment_method'] === $codMethod ) {
			$orderEntity->setCod( $orderValue );
		}
		$this->addExternalCarrierDetails( $moduleOrder, $orderEntity );

		return $orderEntity;
	}

	/**
	 * Adds data to request if applicable.
	 *
	 * @param ModuleOrder\Entity $moduleOrder Order entity.
	 * @param array              $contactInfo Contact info.
	 * @param Entity\Order       $orderEntity CreatePacket request.
	 */
	private function addHomeDeliveryDetails( ModuleOrder\Entity $moduleOrder, array $contactInfo, Entity\Order $orderEntity ): void {
		$address = new Address( $contactInfo['address_1'], $contactInfo['city'], $contactInfo['postcode'] );
		$orderEntity->setDeliveryAddress( $address );
		// Additional address information.
		if ( ! empty( $contactInfo['address_2'] ) ) {
			$orderEntity->setNote( $contactInfo['address_2'] );
		}
	}

	/**
	 * Adds data to request if applicable.
	 *
	 * @param ModuleOrder\Entity $moduleOrder Order entity.
	 * @param Entity\Order       $orderEntity CreatePacket request.
	 */
	private function addExternalCarrierDetails( ModuleOrder\Entity $moduleOrder, Entity\Order $orderEntity ): void {
		$carrier = null;
		if ( $moduleOrder->isExternalCarrier() ) {
			$carrier = $this->carrierRepository->getById( (int) $orderEntity->getCarrierId() );
		}
		$orderEntity->setCarrierRequiresSize( $carrier && $carrier->requiresSize() );
		$size = new Size( $moduleOrder->getLength(), $moduleOrder->getWidth(), $moduleOrder->getHeight() );
		$orderEntity->setSize( $size );
	}

}
