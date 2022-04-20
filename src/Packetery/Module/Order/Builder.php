<?php
/**
 * Class Order
 *
 * @package Packetery\Module\EntityFactory
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Entity;
use Packetery\Core\Entity\Address;
use Packetery\Module\Carrier;
use Packetery\Module\Options\Provider;
use WC_Order;

/**
 * Class Order
 *
 * @package Packetery\Module\EntityFactory
 */
class Builder {

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
	 * Order constructor.
	 *
	 * @param Provider           $optionsProvider    Options Provider.
	 * @param Carrier\Repository $carrierRepository  Carrier repository.
	 */
	public function __construct(
		Provider $optionsProvider,
		Carrier\Repository $carrierRepository
	) {
		$this->optionsProvider   = $optionsProvider;
		$this->carrierRepository = $carrierRepository;
	}

	/**
	 * Creates common order entity from WC_Order.
	 *
	 * @param WC_Order     $order        WC_Order.
	 * @param Entity\Order $partialOrder Partial order.
	 *
	 * @return Entity\Order|null
	 */
	public function finalize( WC_Order $order, Entity\Order $partialOrder ): ?Entity\Order {
		$orderData   = $order->get_data();
		$contactInfo = ( $order->has_shipping_address() ? $orderData['shipping'] : $orderData['billing'] );

		if ( null === $partialOrder->getCarrierId() ) {
			return null;
		}

		$partialOrder->setName( $contactInfo['first_name'] );
		$partialOrder->setSurname( $contactInfo['last_name'] );
		$partialOrder->setEshop( $this->optionsProvider->get_sender() );
		$partialOrder->setValue( (float) $order->get_total( 'raw' ) );

		$address = $partialOrder->getDeliveryAddress();
		if ( null === $address ) {
			$partialOrder->setAddressValidated( false );
			$address = new Address( $contactInfo['address_1'], $contactInfo['city'], $contactInfo['postcode'] );
		}

		$partialOrder->setDeliveryAddress( $address );

		// Shipping address phone is optional.
		$partialOrder->setPhone( $orderData['billing']['phone'] );
		if ( ! empty( $contactInfo['phone'] ) ) {
			$partialOrder->setPhone( $contactInfo['phone'] );
		}
		// Additional address information.
		if ( ! empty( $contactInfo['address_2'] ) ) {
			$partialOrder->setNote( $contactInfo['address_2'] );
		}

		$partialOrder->setEmail( $orderData['billing']['email'] );
		$codMethod = $this->optionsProvider->getCodPaymentMethod();
		if ( $orderData['payment_method'] === $codMethod ) {
			$partialOrder->setCod( $partialOrder->getValue() );
		}
		$partialOrder->setSize( $partialOrder->getSize() );

		if ( $partialOrder->isExternalCarrier() ) {
			$carrier = $this->carrierRepository->getById( (int) $partialOrder->getCarrierId() );
			$partialOrder->setCarrier( $carrier );
		}

		$partialOrder->setCurrency( $order->get_currency() );

		return $partialOrder;
	}
}
