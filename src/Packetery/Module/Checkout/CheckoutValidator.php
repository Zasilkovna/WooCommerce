<?php

declare( strict_types=1 );

namespace Packetery\Module\Checkout;

use Packetery\Core\Api\Rest\PickupPointValidateRequest;
use Packetery\Core\Entity;
use Packetery\Module\Carrier;
use Packetery\Module\Carrier\CarrierOptionsFactory;
use Packetery\Module\Carrier\EntityRepository;
use Packetery\Module\Exception\ProductNotFoundException;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Order;
use Packetery\Module\Order\PickupPointValidator;
use Packetery\Module\Payment\PaymentHelper;
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

	/** @var OptionsProvider */
	private $optionsProvider;

	/** @var PickupPointValidator */
	private $pickupPointValidator;

	/** @var PaymentHelper */
	private $paymentHelper;

	/** @var Carrier\PacketaPickupPointsConfig */
	private $pickupPointsConfig;

	public function __construct(
		WpAdapter $wpAdapter,
		WcAdapter $wcAdapter,
		CheckoutService $checkoutService,
		CartService $cartService,
		SessionService $sessionService,
		CheckoutStorage $storage,
		CarrierOptionsFactory $carrierOptionsFactory,
		EntityRepository $carrierEntityRepository,
		OptionsProvider $optionsProvider,
		PickupPointValidator $pickupPointValidator,
		PaymentHelper $paymentHelper,
		Carrier\PacketaPickupPointsConfig $packetaPickupPointsConfig
	) {
		$this->wpAdapter               = $wpAdapter;
		$this->wcAdapter               = $wcAdapter;
		$this->checkoutService         = $checkoutService;
		$this->cartService             = $cartService;
		$this->sessionService          = $sessionService;
		$this->storage                 = $storage;
		$this->carrierOptionsFactory   = $carrierOptionsFactory;
		$this->carrierEntityRepository = $carrierEntityRepository;
		$this->optionsProvider         = $optionsProvider;
		$this->pickupPointValidator    = $pickupPointValidator;
		$this->paymentHelper           = $paymentHelper;
		$this->pickupPointsConfig      = $packetaPickupPointsConfig;
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
		$this->wcAdapter->sessionSet( PickupPointValidator::VALIDATION_HTTP_ERROR_SESSION_KEY, null );

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
			return $this->validatePickupPoint(
				$checkoutData,
				$carrierId,
				$paymentMethod,
				$this->pickupPointsConfig->getFinalVendorGroups( $carrierOptions->getVendorGroups(), $carrierId )
			);
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

	/**
	 * @param array         $checkoutData
	 * @param string|null   $carrierId
	 * @param string|null   $paymentMethod
	 * @param string[]|null $vendorGroups
	 *
	 * @return string|null
	 * @throws ProductNotFoundException
	 */
	private function validatePickupPoint(
		array $checkoutData,
		?string $carrierId,
		?string $paymentMethod,
		?array $vendorGroups
	): ?string {
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

		if (
			! $this->carrierEntityRepository->isValidForCountry(
				$carrierId,
				$customerCountry
			)
		) {
			return $this->wpAdapter->__( 'The selected Packeta carrier is not available for the selected delivery country.', 'packeta' );
		}

		if ( $this->optionsProvider->isPickupPointValidationEnabled() ) {
			$pickupPointId = $checkoutData[ Order\Attribute::POINT_ID ];
			if ( $carrierId === '' ) {
				$carrierId = Entity\Carrier::INTERNAL_PICKUP_POINTS_ID;
			}
			$pickupPointValidationResponse = $this->pickupPointValidator->validate(
				$this->createPickupPointValidateRequest(
					$pickupPointId,
					$carrierId,
					( is_numeric( $carrierId ) ? $pickupPointId : null ),
					$paymentMethod,
					$vendorGroups
				)
			);
			if ( ! $pickupPointValidationResponse->isValid() ) {
				return $this->wpAdapter->__( 'The selected Packeta pickup point could not be validated. Please select another.', 'packeta' );
			}
		}

		return null;
	}

	/**
	 * @param string        $pickupPointId
	 * @param string|null   $carrierId
	 * @param string|null   $pointCarrierId
	 * @param string|null   $paymentMethod
	 * @param string[]|null $vendorGroups
	 *
	 * @return PickupPointValidateRequest
	 * @throws ProductNotFoundException
	 */
	private function createPickupPointValidateRequest(
		string $pickupPointId,
		?string $carrierId,
		?string $pointCarrierId,
		?string $paymentMethod,
		?array $vendorGroups
	): PickupPointValidateRequest {
		return new PickupPointValidateRequest(
			$pickupPointId,
			$carrierId,
			$pointCarrierId,
			$this->checkoutService->getCustomerCountry(),
			null,
			null,
			$this->cartService->getCartWeightKg(),
			$this->cartService->isAgeVerificationRequired(),
			null,
			( $paymentMethod !== null && $this->paymentHelper->isCodPaymentMethod( $paymentMethod ) === true ),
			$this->cartService->getBiggestProductSize(),
			$vendorGroups
		);
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
