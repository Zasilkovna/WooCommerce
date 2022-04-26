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
use Packetery\Module\Calculator;
use Packetery\Module\Carrier;
use Packetery\Module\Options\Provider;
use Packetery\Module\Product;
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
	 * Weight calculator.
	 *
	 * @var Calculator
	 */
	private $calculator;

	/**
	 * Order constructor.
	 *
	 * @param Provider           $optionsProvider Options Provider.
	 * @param Carrier\Repository $carrierRepository Carrier repository.
	 * @param Calculator         $calculator Weight calculator.
	 */
	public function __construct(
		Provider $optionsProvider,
		Carrier\Repository $carrierRepository,
		Calculator $calculator
	) {
		$this->optionsProvider   = $optionsProvider;
		$this->carrierRepository = $carrierRepository;
		$this->calculator        = $calculator;
	}

	/**
	 * Creates common order entity from WC_Order.
	 *
	 * @param WC_Order     $wcOrder WC_Order.
	 * @param Entity\Order $order   Partial order.
	 *
	 * @return Entity\Order
	 */
	public function finalize( WC_Order $wcOrder, Entity\Order $order ): Entity\Order {
		$calculatedWeight = $this->calculator->calculateOrderWeight( $wcOrder );
		if ( null === $order->getWeight() ) {
			$order->setWeight( $calculatedWeight );
		}
		$order->setCalculatedWeight( $calculatedWeight );
		$order->setAdultContent( $this->containsAdultContent( $wcOrder ) );
		$order->setShippingCountry( strtolower( $wcOrder->get_shipping_country() ) );

		$orderData   = $wcOrder->get_data();
		$contactInfo = ( $wcOrder->has_shipping_address() ? $orderData['shipping'] : $orderData['billing'] );

		$order->setName( $contactInfo['first_name'] );
		$order->setSurname( $contactInfo['last_name'] );
		$order->setEshop( $this->optionsProvider->get_sender() );
		$order->setValue( (float) $wcOrder->get_total( 'raw' ) );

		$address = $order->getDeliveryAddress();
		if ( null === $address ) {
			$order->setAddressValidated( false );
			$address = new Address( $contactInfo['address_1'], $contactInfo['city'], $contactInfo['postcode'] );
		}

		$order->setDeliveryAddress( $address );

		// Shipping address phone is optional.
		$order->setPhone( $orderData['billing']['phone'] );
		if ( ! empty( $contactInfo['phone'] ) ) {
			$order->setPhone( $contactInfo['phone'] );
		}
		// Additional address information.
		if ( ! empty( $contactInfo['address_2'] ) ) {
			$order->setNote( $contactInfo['address_2'] );
		}

		$order->setEmail( $orderData['billing']['email'] );
		$codMethod = $this->optionsProvider->getCodPaymentMethod();
		if ( $orderData['payment_method'] === $codMethod ) {
			$order->setCod( $order->getValue() );
		}
		$order->setSize( $order->getSize() );

		if ( $order->isExternalCarrier() ) {
			$carrier = $this->carrierRepository->getById( (int) $order->getCarrierId() );
			$order->setCarrier( $carrier );
		}

		$order->setCurrency( $wcOrder->get_currency() );

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
			$itemData      = $item->get_data();
			$productEntity = Product\Entity::fromPostId( $itemData['product_id'] );
			if ( $productEntity->isPhysical() && $productEntity->isAgeVerification18PlusRequired() ) {
				return true;
			}
		}

		return false;
	}

}
