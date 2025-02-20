<?php

declare( strict_types=1 );

namespace Packetery\Module\Checkout;

use Packetery\Core\Entity;
use Packetery\Module\Carrier;
use Packetery\Module\EntityFactory\SizeFactory;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Order;
use Packetery\Module\Order\PickupPointValidator;
use WC_Data_Exception;
use WC_Order;

class OrderUpdater {

	/**
	 * @var Order\Repository
	 */
	private $orderRepository;

	/**
	 * @var CheckoutService
	 */
	private $checkoutService;

	/**
	 * @var CheckoutStorage
	 */
	private $storage;

	/**
	 * @var WcAdapter
	 */
	private $wcAdapter;

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * @var OptionsProvider
	 */
	private $optionsProvider;

	/**
	 * @var Order\AttributeMapper
	 */
	private $mapper;

	/**
	 * @var Carrier\EntityRepository
	 */
	private $carrierEntityRepository;

	/**
	 * @var CartService
	 */
	private $cartService;

	/**
	 * @var Order\PacketAutoSubmitter
	 */
	private $packetAutoSubmitter;

	/**
	 * @var SizeFactory
	 */
	private $sizeFactory;

	public function __construct(
		Order\Repository $orderRepository,
		CheckoutService $checkoutService,
		CheckoutStorage $checkoutStorage,
		WcAdapter $wcAdapter,
		WpAdapter $wpAdapter,
		OptionsProvider $optionsProvider,
		Order\AttributeMapper $attributeMapper,
		Carrier\EntityRepository $carrierEntityRepository,
		CartService $cartService,
		Order\PacketAutoSubmitter $packetAutoSubmitter,
		SizeFactory $sizeFactory
	) {
		$this->orderRepository         = $orderRepository;
		$this->checkoutService         = $checkoutService;
		$this->storage                 = $checkoutStorage;
		$this->wcAdapter               = $wcAdapter;
		$this->wpAdapter               = $wpAdapter;
		$this->optionsProvider         = $optionsProvider;
		$this->mapper                  = $attributeMapper;
		$this->carrierEntityRepository = $carrierEntityRepository;
		$this->cartService             = $cartService;
		$this->packetAutoSubmitter     = $packetAutoSubmitter;
		$this->sizeFactory             = $sizeFactory;
	}

	/**
	 * Saves pickup point and other Packeta information to order.
	 *
	 * @throws WC_Data_Exception When invalid data are passed during shipping address update.
	 */
	public function actionUpdateOrderById( int $orderId ): void {
		$wcOrder = $this->orderRepository->getWcOrderById( $orderId );
		if ( $wcOrder === null ) {
			return;
		}

		$this->actionUpdateOrder( $wcOrder );
	}

	/**
	 * Saves pickup point and other Packeta information to order.
	 *
	 * @throws WC_Data_Exception When invalid data are passed during shipping address update.
	 */
	public function actionUpdateOrder( WC_Order $wcOrder ): void {
		$chosenMethod = $this->checkoutService->resolveChosenMethod();
		if (
			$chosenMethod === null ||
			$this->checkoutService->isPacketeryShippingMethod( $chosenMethod ) === false
		) {
			return;
		}

		$checkoutData           = $this->storage->getPostDataIncludingStoredData( $chosenMethod, $wcOrder->get_id() );
		$propsToSave            = [];
		$carrierId              = $this->checkoutService->getCarrierIdFromPacketeryShippingMethod( $chosenMethod );
		$orderHasUnsavedChanges = false;

		$propsToSave[ Order\Attribute::CARRIER_ID ] = $carrierId;

		if ( $this->checkoutService->isPickupPointOrder() ) {
			$this->addPickupPointValidationError( $wcOrder );

			if ( count( $checkoutData ) === 0 ) {
				return;
			}
			$propsToSave = $this->getPropsFromCheckoutData( $checkoutData, $propsToSave, $wcOrder );

			$orderHasUnsavedChanges = true;
		}

		$carrier = $this->carrierEntityRepository->getAnyById( $carrierId );
		if ( $carrier === null ) {
			return;
		}
		$order = new Entity\Order( (string) $wcOrder->get_id(), $carrier );

		$orderHasUnsavedChanges = $this->updateHomeDelivery(
			$checkoutData,
			$order,
			$wcOrder,
			$orderHasUnsavedChanges
		);
		if ( $orderHasUnsavedChanges ) {
			$wcOrder->save();
		}

		$this->updateCarDelivery( $checkoutData, $order );

		if ( $this->cartService->getCartWeightKg() === 0.0 && $this->optionsProvider->isDefaultWeightEnabled() === true ) {
			$order->setWeight( $this->optionsProvider->getDefaultWeight() + $this->optionsProvider->getPackagingWeight() );
		}

		if (
			$carrier->requiresSize() === true &&
			$this->optionsProvider->isDefaultDimensionsEnabled() === true
		) {
			$order->setSize( $this->sizeFactory->createDefaultSizeForNewOrder() );
		}

		$pickupPoint = $this->mapper->toOrderEntityPickupPoint( $order, $propsToSave );
		$order->setPickupPoint( $pickupPoint );

		$this->storage->deleteTransient();
		$this->orderRepository->save( $order );
		$this->packetAutoSubmitter->handleEventAsync( Order\PacketAutoSubmitter::EVENT_ON_ORDER_CREATION_FE, $wcOrder->get_id() );
	}

	/** @phpstan-ignore-next-line */
	private function addPickupPointValidationError( WC_Order $wcOrder ): void {
		// @phpstan-ignore-next-line
		if ( PickupPointValidator::IS_ACTIVE ) {
			$pickupPointValidationError = $this->wcAdapter->sessionGetString( PickupPointValidator::VALIDATION_HTTP_ERROR_SESSION_KEY );
			if ( $pickupPointValidationError !== null ) {
				$wcOrder->add_order_note(
					sprintf(
						// translators: %s: Message from downloader.
						$this->wpAdapter->__( 'The selected Packeta pickup point could not be validated, reason: %s.', 'packeta' ),
						$pickupPointValidationError
					)
				);
				$this->wcAdapter->sessionSet( PickupPointValidator::VALIDATION_HTTP_ERROR_SESSION_KEY, null );
			}
		}
	}

	/**
	 * @throws WC_Data_Exception When invalid data are passed during shipping address update.
	 */
	private function getPropsFromCheckoutData( array $checkoutData, array $propsToSave, WC_Order $wcOrder ): array {
		foreach ( Order\Attribute::$pickupPointAttributes as $attr ) {
			$attrName = $attr['name'];
			if ( ! isset( $checkoutData[ $attrName ] ) ) {
				continue;
			}
			$attrValue = $checkoutData[ $attrName ];

			$saveMeta = true;
			if (
				$attrName === Order\Attribute::CARRIER_ID ||
				( $attrName === Order\Attribute::POINT_URL && ! filter_var( $attrValue, FILTER_VALIDATE_URL ) )
			) {
				$saveMeta = false;
			}
			if ( $saveMeta ) {
				$propsToSave[ $attrName ] = $attrValue;
			}

			if ( $this->optionsProvider->replaceShippingAddressWithPickupPointAddress() ) {
				$this->mapper->toWcOrderShippingAddress( $wcOrder, $attrName, (string) $attrValue );
			}
		}

		return $propsToSave;
	}

	private function updateHomeDelivery( array $checkoutData, Entity\Order $orderEntity, WC_Order $wcOrder, bool $orderHasUnsavedChanges ): bool {
		if (
			! isset( $checkoutData[ Order\Attribute::ADDRESS_IS_VALIDATED ] ) ||
			$checkoutData[ Order\Attribute::ADDRESS_IS_VALIDATED ] !== '1' ||
			! $this->checkoutService->isHomeDeliveryOrder()
		) {
			return $orderHasUnsavedChanges;
		}

		$validatedAddress = $this->mapper->toValidatedAddress( $checkoutData );
		$orderEntity->setDeliveryAddress( $validatedAddress );
		$orderEntity->setAddressValidated( true );
		if ( $this->checkoutService->areBlocksUsedInCheckout() ) {
			$this->mapper->validatedAddressToWcOrderShippingAddress( $wcOrder, $checkoutData );
			$orderHasUnsavedChanges = true;
		}

		return $orderHasUnsavedChanges;
	}

	/**
	 * @param array        $checkoutData
	 * @param Entity\Order $orderEntity
	 *
	 * @return void
	 */
	private function updateCarDelivery( array $checkoutData, Entity\Order $orderEntity ): void {
		if ( count( $checkoutData ) <= 0 || ! $this->checkoutService->isCarDeliveryOrder() ) {
			return;
		}
		$address = $this->mapper->toCarDeliveryAddress( $checkoutData );
		$orderEntity->setDeliveryAddress( $address );
		$orderEntity->setAddressValidated( true );
		$orderEntity->setCarDeliveryId( $checkoutData[ Order\Attribute::CAR_DELIVERY_ID ] );
	}
}
