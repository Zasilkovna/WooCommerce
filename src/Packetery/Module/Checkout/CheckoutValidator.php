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
use WC_REST_Exception;
use WP_Error;

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
	 * Using wc_add_notice is not safe because it can be cleared by other plugins.
	 *
	 * @param array<string, string|int|string[]|bool> $data
	 * @param WP_Error                                $wpError
	 *
	 * @throws ProductNotFoundException
	 */
	public function actionValidateCheckoutData( array $data, WP_Error $wpError ): void {
		$error = $this->getFirstError();
		if ( $error !== null ) {
			$wpError->add( 'packeta_cart_validation_failed', $error );
		}
	}

	/**
	 * Using wc_add_notice works even for block checkout, but WC_REST_Exception is recommended.
	 *
	 * @throws WC_REST_Exception
	 * @throws ProductNotFoundException
	 */
	public function actionValidateBlockCheckoutData(): void {
		$error = $this->getFirstError();
		if ( $error !== null ) {
			throw new WC_REST_Exception( 'packeta_cart_validation_failed', $error );
		}
	}

	/**
	 * @throws ProductNotFoundException
	 */
	private function getFirstError(): ?string {
		$chosenShippingMethod = $this->checkoutService->resolveChosenMethod();

		if (
			$chosenShippingMethod === null ||
			$this->checkoutService->isPacketeryShippingMethod( $chosenShippingMethod ) === false
		) {
			return null;
		}

		$checkoutData = $this->storage->getPostDataIncludingStoredData( $chosenShippingMethod );

		if ( $this->cartService->isShippingRateRestrictedByProductsCategory( $chosenShippingMethod, $this->wcAdapter->cartGetCartContents() ) ) {
			return $this->wpAdapter->__( 'Chosen delivery method is no longer available. Please choose another delivery method.', 'packeta' );
		}

		$carrierId      = $this->checkoutService->getCarrierIdFromPacketeryShippingMethod( $chosenShippingMethod );
		$carrierOptions = $this->carrierOptionsFactory->createByCarrierId( $carrierId );
		$paymentMethod  = $this->sessionService->getChosenPaymentMethod();

		if ( $paymentMethod !== null && $carrierOptions->hasCheckoutPaymentMethodDisallowed( $paymentMethod ) ) {
			return $this->wpAdapter->__( 'Chosen delivery method is no longer available. Please choose another delivery method.', 'packeta' );
		}

		if ( $this->checkoutService->isPickupPointOrder() ) {
			return $this->validatePickupPoint( $checkoutData, $carrierId );
		}

		if ( $this->checkoutService->isHomeDeliveryOrder() ) {
			return $this->validateHomeDelivery( $checkoutData, $carrierId );
		}

		if (
			( ! isset( $checkoutData[ Order\Attribute::CAR_DELIVERY_ID ] ) || $checkoutData[ Order\Attribute::CAR_DELIVERY_ID ] === '' ) &&
			$this->checkoutService->isCarDeliveryOrder()
		) {
			return $this->wpAdapter->__( 'Delivery address has not been verified. Verification of delivery address is required by this carrier.', 'packeta' );
		}

		return null;
	}

	private function validatePickupPoint( array $checkoutData, ?string $carrierId ): ?string {
		$requiredAttributes = array_filter(
			array_combine(
				array_column( Order\Attribute::$pickupPointAttributes, 'name' ),
				array_column( Order\Attribute::$pickupPointAttributes, 'required' )
			)
		);
		foreach ( $requiredAttributes as $attr => $required ) {
			$attrValue = $checkoutData[ $attr ] ?? null;
			if ( ! $attrValue ) {
				return $this->wpAdapter->__( 'Pickup point is not chosen.', 'packeta' );
			}
		}

		$customerCountry = $this->checkoutService->getCustomerCountry();
		if ( $customerCountry === null ) {
			return $this->wpAdapter->__( 'Customer country could not be obtained.', 'packeta' );
		}

		if ( ! $this->carrierEntityRepository->isValidForCountry( $carrierId, $customerCountry ) ) {
			return $this->wpAdapter->__( 'The selected Packeta carrier is not available for the selected delivery country.', 'packeta' );
		}

		return null;
	}

	private function validateHomeDelivery( array $checkoutData, ?string $carrierId ): ?string {
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
			return $this->wpAdapter->__( 'Delivery address has not been verified. Verification of delivery address is required by this carrier.', 'packeta' );
		}

		return null;
	}
}
