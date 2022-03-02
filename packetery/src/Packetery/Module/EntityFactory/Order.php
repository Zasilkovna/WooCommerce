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
use Packetery\Module\Carrier;
use Packetery\Module\EntityFactory;
use Packetery\Module\Options\Provider;
use Packetery\Module\Order as ModuleOrder;
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
	 * PickupPoint factory.
	 *
	 * @var PickupPoint
	 */
	private $pickupPointFactory;

	/**
	 * Order repository.
	 *
	 * @var ModuleOrder\DbRepository
	 */
	private $orderRepository;

	/**
	 * Order constructor.
	 *
	 * @param Provider                  $optionsProvider    Options Provider.
	 * @param Carrier\Repository        $carrierRepository  Carrier repository.
	 * @param ModuleAddress\Repository  $addressRepository  Address repository.
	 * @param EntityFactory\PickupPoint $pickupPointFactory PickupPoint factory.
	 * @param ModuleOrder\DbRepository  $orderRepository    Order repository.
	 */
	public function __construct(
		Provider $optionsProvider,
		Carrier\Repository $carrierRepository,
		ModuleAddress\Repository $addressRepository,
		EntityFactory\PickupPoint $pickupPointFactory,
		ModuleOrder\DbRepository $orderRepository
	) {
		$this->optionsProvider    = $optionsProvider;
		$this->carrierRepository  = $carrierRepository;
		$this->addressRepository  = $addressRepository;
		$this->pickupPointFactory = $pickupPointFactory;
		$this->orderRepository    = $orderRepository;
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
		$moduleOrder = new ModuleOrder\Entity( $order, $this->orderRepository );

		if ( null === $moduleOrder->getCarrierId() ) {
			return null;
		}

		$orderWeight = $moduleOrder->getUserSpecifiedWeight();
		if ( null === $orderWeight ) {
			$orderWeight = $this->calculateOrderWeight( $order );
		}

		$orderEntity = new Entity\Order(
			$orderId,
			$contactInfo['first_name'],
			$contactInfo['last_name'],
			$moduleOrder->getTotalPrice(),
			Helper::simplifyWeight( $orderWeight ),
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
			$orderEntity->setCod( $moduleOrder->getTotalPrice() );
		}
		$size = new Size( $moduleOrder->getLength(), $moduleOrder->getWidth(), $moduleOrder->getHeight() );
		$orderEntity->setSize( $size );

		if ( $orderEntity->isExternalCarrier() ) {
			$carrier = $this->carrierRepository->getById( (int) $orderEntity->getCarrierId() );
			$orderEntity->setCarrier( $carrier );
		}

		$orderEntity->setCurrency( $order->get_currency() );

		return $orderEntity;
	}

	/**
	 * Calculates order weight ignoring user specified weight.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return float
	 */
	public function calculateOrderWeight( WC_Order $order ): float {
		$weight = 0;
		foreach ( $order->get_items() as $item ) {
			$quantity      = $item->get_quantity();
			$product       = $item->get_product();
			$productWeight = (float) $product->get_weight();
			$weight       += ( $productWeight * $quantity );
		}

		$weightKg = wc_get_weight( $weight, 'kg' );
		if ( $weightKg ) {
			$weightKg += $this->optionsProvider->getPackagingWeight();
		}

		return $weightKg;
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

		if ( ! $order instanceof WC_Order ) {
			return null;
		}

		return $this->create( $order );
	}

}
