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
use Packetery\Module\CustomsDeclaration;
use Packetery\Module\Helper;
use Packetery\Module\WeightCalculator;
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
	 * Order constructor.
	 *
	 * @param Provider                      $optionsProvider              Options Provider.
	 * @param WeightCalculator              $calculator                   Weight calculator.
	 * @param CustomsDeclaration\Repository $customsDeclarationRepository Customs declaration repository.
	 */
	public function __construct(
		Provider $optionsProvider,
		WeightCalculator $calculator,
		CustomsDeclaration\Repository $customsDeclarationRepository
	) {
		$this->optionsProvider              = $optionsProvider;
		$this->calculator                   = $calculator;
		$this->customsDeclarationRepository = $customsDeclarationRepository;
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
		$order->setCalculatedWeight( $this->calculator->calculateOrderWeight( $wcOrder ) );

		if ( null === $order->containsAdultContent() ) {
			$order->setAdultContent( $this->containsAdultContent( $wcOrder ) );
		}

		$order->setShippingCountry( Helper::getWcOrderCountry( $wcOrder ) );

		$orderData   = $wcOrder->get_data();
		$contactInfo = ( $wcOrder->has_shipping_address() ? $orderData['shipping'] : $orderData['billing'] );

		$order->setName( $contactInfo['first_name'] );
		$order->setSurname( $contactInfo['last_name'] );
		$order->setEshop( $this->optionsProvider->get_sender() );

		if ( null === $order->getValue() ) {
			$order->setValue( (float) $wcOrder->get_total( 'raw' ) );
		}

		$address = $order->getDeliveryAddress();
		if ( null === $address ) {
			$order->setAddressValidated( false );
			$address = new Address( $contactInfo['address_1'], $contactInfo['city'], $contactInfo['postcode'] );
		}

		$order->setDeliveryAddress( $address );
		$order->setCustomNumber( $wcOrder->get_order_number() );

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
		if ( null === $order->getCod() && $orderData['payment_method'] === $codMethod ) {
			$order->setCod( $order->getValue() );
		}

		if ( $orderData['payment_method'] !== $codMethod ) {
			$order->setCod( null );
		}

		$order->setSize( $order->getSize() );
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
			$product = $item->get_product();
			if ( $product ) {
				$productEntity = new Product\Entity( $product );
				if ( $productEntity->isPhysical() && $productEntity->isAgeVerification18PlusRequired() ) {
					return true;
				}
			}
		}

		return false;
	}

}
