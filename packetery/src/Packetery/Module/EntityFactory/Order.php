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
use Packetery\Module\EntityFactory;
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
	 * PickupPoint factory.
	 *
	 * @var PickupPoint
	 */
	private $pickupPointFactory;

	/**
	 * Order constructor.
	 *
	 * @param Provider                  $optionsProvider   Options Provider.
	 * @param Repository                $carrierRepository Carrier repository.
	 * @param ModuleAddress\Repository  $addressRepository Address repository.
	 * @param EntityFactory\PickupPoint $pickupPointFactory PickupPoint factory.
	 */
	public function __construct(
		Provider $optionsProvider,
		Repository $carrierRepository,
		ModuleAddress\Repository $addressRepository,
		EntityFactory\PickupPoint $pickupPointFactory
	) {
		$this->optionsProvider    = $optionsProvider;
		$this->carrierRepository  = $carrierRepository;
		$this->addressRepository  = $addressRepository;
		$this->pickupPointFactory = $pickupPointFactory;
	}

	/**
	 * Creates common order entity from WC_Order.
	 *
	 * @param WC_Order $order WC_Order.
	 *
	 * @return Entity\Order|null
	 */
	public function create( WC_Order $order ): ?Entity\Order {
		$orderData   = $order->get_data();
		$orderId     = (string) $orderData['id'];
		$contactInfo = ( $order->has_shipping_address() ? $orderData['shipping'] : $orderData['billing'] );
		// Type cast of $orderTotalPrice is needed, PHPDoc is wrong.
		$orderValue  = (float) $order->get_total( 'raw' );
		$moduleOrder = new ModuleOrder\Entity( $order );

		if ( null === $moduleOrder->getCarrierId() ) {
			return null;
		}

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

		if ( $moduleOrder->getPointId() ) {
			$pickupPoint = $this->pickupPointFactory->create( $moduleOrder );
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

		if ( $orderEntity->isExternalCarrier() ) {
			$carrier = $this->carrierRepository->getById( (int) $orderEntity->getCarrierId() );
			$orderEntity->setCarrier( $carrier );
		}

		return $orderEntity;
	}

	/**
	 * Creates entity from global variables.
	 *
	 * @return Entity\Order|null
	 */
	public function fromGlobals(): ?Entity\Order {
		global $post;

		return $this->fromPostId( $post->ID );
	}

	/**
	 * Creates entity from post id.
	 *
	 * @param int|string $postId Post id.
	 *
	 * @return Entity\Order|null
	 */
	public function fromPostId( $postId ): ?Entity\Order {
		$order = wc_get_order( $postId );

		return $this->create( $order );
	}

}
