<?php

declare( strict_types=1 );

namespace Packetery\Module\Checkout;

use Packetery\Module\Carrier;
use Packetery\Module\Carrier\CarrierOptionsFactory;
use Packetery\Module\Carrier\EntityRepository;
use Packetery\Module\Exception\ProductNotFoundException;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Order;

class CheckoutValidator {

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * @var WcAdapter
	 */
	private $wcAdapter;

	/**
	 * @var CheckoutService
	 */
	private $checkoutService;

	/**
	 * @var CartService
	 */
	private $cartService;

	/**
	 * @var SessionService
	 */
	private $sessionService;

	/**
	 * @var CheckoutStorage
	 */
	private $storage;

	/**
	 * @var CarrierOptionsFactory
	 */
	private $carrierOptionsFactory;

	/**
	 * @var EntityRepository
	 */
	private $carrierEntityRepository;

	public function __construct(
		WpAdapter $wpAdapter,
		WcAdapter $wcAdapter,
		CheckoutService $checkoutService,
		CartService $cartService,
		SessionService $sessionService,
		CheckoutStorage $storage,
		CarrierOptionsFactory $carrierOptionsFactory,
		EntityRepository $carrierEntityRepository
	) {
		$this->wpAdapter               = $wpAdapter;
		$this->wcAdapter               = $wcAdapter;
		$this->checkoutService         = $checkoutService;
		$this->cartService             = $cartService;
		$this->sessionService          = $sessionService;
		$this->storage                 = $storage;
		$this->carrierOptionsFactory   = $carrierOptionsFactory;
		$this->carrierEntityRepository = $carrierEntityRepository;
	}

	/**
	 * Checks if all attributes required for chosen method are set, sets an error otherwise.
	 *
	 * @throws ProductNotFoundException Product not found.
	 */
	public function actionValidateCheckoutData(): void {
		$chosenShippingMethod = $this->checkoutService->resolveChosenMethod();

		if (
			$chosenShippingMethod === null ||
			$this->checkoutService->isPacketeryShippingMethod( $chosenShippingMethod ) === false
		) {
			return;
		}

		$checkoutData = $this->storage->getPostDataIncludingStoredData( $chosenShippingMethod );

		if ( $this->cartService->isShippingRateRestrictedByProductsCategory( $chosenShippingMethod, $this->wcAdapter->cartGetCartContents() ) ) {
			$this->wcAdapter->addNotice( $this->wpAdapter->__( 'Chosen delivery method is no longer available. Please choose another delivery method.', 'packeta' ), 'error' );

			return;
		}

		$carrierId      = $this->checkoutService->getCarrierIdFromPacketeryShippingMethod( $chosenShippingMethod );
		$carrierOptions = $this->carrierOptionsFactory->createByCarrierId( $carrierId );
		$paymentMethod  = $this->sessionService->getChosenPaymentMethod();

		if ( $paymentMethod !== null && $carrierOptions->hasCheckoutPaymentMethodDisallowed( $paymentMethod ) ) {
			$this->wcAdapter->addNotice( $this->wpAdapter->__( 'Chosen delivery method is no longer available. Please choose another delivery method.', 'packeta' ), 'error' );

			return;
		}

		if ( $this->checkoutService->isPickupPointOrder() ) {
			$this->validatePickupPoint( $checkoutData, $carrierId );

			return;
		}

		if ( $this->checkoutService->isHomeDeliveryOrder() ) {
			$this->validateHomeDelivery( $checkoutData, $carrierId );

			return;
		}

		if (
			( ! isset( $checkoutData[ Order\Attribute::CAR_DELIVERY_ID ] ) || $checkoutData[ Order\Attribute::CAR_DELIVERY_ID ] === '' ) &&
			$this->checkoutService->isCarDeliveryOrder()
		) {
			$this->wcAdapter->addNotice( $this->wpAdapter->__( 'Delivery address has not been verified. Verification of delivery address is required by this carrier.', 'packeta' ), 'error' );
		}
	}

	private function validatePickupPoint( array $checkoutData, ?string $carrierId ): void {
		$error = false;

		$requiredAttributes = array_filter(
			array_combine(
				array_column( Order\Attribute::$pickupPointAttributes, 'name' ),
				array_column( Order\Attribute::$pickupPointAttributes, 'required' )
			)
		);
		foreach ( $requiredAttributes as $attr => $required ) {
			$attrValue = $checkoutData[ $attr ] ?? null;
			if ( ! $attrValue ) {
				$error = true;
			}
		}
		if ( $error ) {
			$this->wcAdapter->addNotice( $this->wpAdapter->__( 'Pickup point is not chosen.', 'packeta' ), 'error' );
		}

		$customerCountry = $this->checkoutService->getCustomerCountry();
		if (
			! $error &&
			$customerCountry === null
		) {
			$this->wcAdapter->addNotice( $this->wpAdapter->__( 'Customer country could not be obtained.', 'packeta' ), 'error' );
			$error = true;
		}

		if (
			! $error &&
			! $this->carrierEntityRepository->isValidForCountry(
				$carrierId,
				$customerCountry
			)
		) {
			$this->wcAdapter->addNotice( $this->wpAdapter->__( 'The selected Packeta carrier is not available for the selected delivery country.', 'packeta' ), 'error' );
		}
	}

	private function validateHomeDelivery( array $checkoutData, ?string $carrierId ): void {
		$optionId      = Carrier\OptionPrefixer::getOptionId( $carrierId );
		$carrierOption = $this->wpAdapter->getOption( $optionId );

		$addressValidation = 'none';
		if ( $carrierOption !== false ) {
			$addressValidation = ( $carrierOption['address_validation'] ?? $addressValidation );
		}

		if (
			$addressValidation === 'required' &&
			(
				! isset( $checkoutData[ Order\Attribute::ADDRESS_IS_VALIDATED ] ) ||
				$checkoutData[ Order\Attribute::ADDRESS_IS_VALIDATED ] !== '1'
			)
		) {
			$this->wcAdapter->addNotice( $this->wpAdapter->__( 'Delivery address has not been verified. Verification of delivery address is required by this carrier.', 'packeta' ), 'error' );
		}
	}
}
