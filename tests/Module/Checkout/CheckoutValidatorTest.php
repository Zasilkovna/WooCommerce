<?php

declare( strict_types=1 );

namespace Tests\Module\Checkout;

use Packetery\Module\Carrier\CarrierOptionsFactory;
use Packetery\Module\Carrier\EntityRepository;
use Packetery\Module\Carrier\Options as CarrierOptions;
use Packetery\Module\Checkout\CartService;
use Packetery\Module\Checkout\CheckoutService;
use Packetery\Module\Checkout\CheckoutStorage;
use Packetery\Module\Checkout\CheckoutValidator;
use Packetery\Module\Checkout\SessionService;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use WC_REST_Exception;

class CheckoutValidatorTest extends TestCase {

	private WpAdapter&MockObject $wpAdapter;
	private WcAdapter&MockObject $wcAdapter;
	private CheckoutService&MockObject $checkoutService;
	private CartService&MockObject $cartService;
	private SessionService&MockObject $sessionService;
	private CheckoutStorage&MockObject $checkoutStorage;
	private CarrierOptionsFactory&MockObject $carrierOptionsFactory;
	private EntityRepository&MockObject $carrierEntityRepository;

	private function createCheckoutValidator(): CheckoutValidator {
		$this->wpAdapter               = $this->createMock( WpAdapter::class );
		$this->wcAdapter               = $this->createMock( WcAdapter::class );
		$this->checkoutService         = $this->createMock( CheckoutService::class );
		$this->cartService             = $this->createMock( CartService::class );
		$this->sessionService          = $this->createMock( SessionService::class );
		$this->checkoutStorage         = $this->createMock( CheckoutStorage::class );
		$this->carrierOptionsFactory   = $this->createMock( CarrierOptionsFactory::class );
		$this->carrierEntityRepository = $this->createMock( EntityRepository::class );

		return new CheckoutValidator(
			$this->wpAdapter,
			$this->wcAdapter,
			$this->checkoutService,
			$this->cartService,
			$this->sessionService,
			$this->checkoutStorage,
			$this->carrierOptionsFactory,
			$this->carrierEntityRepository
		);
	}

	/**
	 * @return array<string, array{callable, bool, string|null}>
	 */
	public static function actionValidateBlockCheckoutDataProvider(): array {
		return [
			'not packetery shipping method'        => [
				'scenarioParameters' => [
					'resolveChosenMethod'       => 'third_party_method',
					'isPacketeryShippingMethod' => false,
				],
				'shouldThrow'        => false,
			],
			'category restriction'                 => [
				'scenarioParameters' => [
					'resolveChosenMethod'            => 'packetery_method',
					'isPacketeryShippingMethod'      => true,
					'getPostDataIncludingStoredData' => [],
					'cartGetCartContents'            => [],
					'isShippingRateRestrictedByProductsCategory' => true,
				],
				'shouldThrow'        => true,
			],
			'payment method disallowed'            => [
				'scenarioParameters' => [
					'resolveChosenMethod'            => 'packetery_method',
					'isPacketeryShippingMethod'      => true,
					'getPostDataIncludingStoredData' => [],
					'cartGetCartContents'            => [],
					'isShippingRateRestrictedByProductsCategory' => false,
					'getCarrierIdFromPacketeryShippingMethod' => '789',
					'createByCarrierId'              => new CarrierOptions( 'dummyId', [ 'disallowed_checkout_payment_methods' => [ 'cod' ] ] ),
					'getChosenPaymentMethod'         => 'cod',
				],
				'shouldThrow'        => true,
			],
			'car delivery missing validation'      => [
				'scenarioParameters' => [
					'resolveChosenMethod'            => 'packetery_method',
					'isPacketeryShippingMethod'      => true,
					'getPostDataIncludingStoredData' => [],
					'cartGetCartContents'            => [],
					'isShippingRateRestrictedByProductsCategory' => false,
					'getCarrierIdFromPacketeryShippingMethod' => '456',
					'getChosenPaymentMethod'         => null,
					'isPickupPointOrder'             => false,
					'isHomeDeliveryOrder'            => false,
					'isCarDeliveryOrder'             => true,
				],
				'shouldThrow'        => true,
			],
			'pickup point ok'                      => [
				'scenarioParameters' => [
					'resolveChosenMethod'            => 'packetery_method',
					'isPacketeryShippingMethod'      => true,
					'getPostDataIncludingStoredData' => [ 'packetery_point_id' => '123' ],
					'cartGetCartContents'            => [],
					'isShippingRateRestrictedByProductsCategory' => false,
					'getCarrierIdFromPacketeryShippingMethod' => 'dummyPpId',
					'getChosenPaymentMethod'         => null,
					'isPickupPointOrder'             => true,
					'getCustomerCountry'             => 'CZ',
					'isValidForCountry'              => true,
				],
				'shouldThrow'        => false,
			],
			'pickup point missing'                 => [
				'scenarioParameters' => [
					'resolveChosenMethod'            => 'packetery_method',
					'isPacketeryShippingMethod'      => true,
					'getPostDataIncludingStoredData' => [],
					'cartGetCartContents'            => [],
					'isShippingRateRestrictedByProductsCategory' => false,
					'getCarrierIdFromPacketeryShippingMethod' => 'dummyPpId',
					'getChosenPaymentMethod'         => null,
					'isPickupPointOrder'             => true,
					'getCustomerCountry'             => 'CZ',
					'isValidForCountry'              => true,
				],
				'shouldThrow'        => true,
			],
			'pickup point no country'              => [
				'scenarioParameters' => [
					'resolveChosenMethod'            => 'packetery_method',
					'isPacketeryShippingMethod'      => true,
					'getPostDataIncludingStoredData' => [ 'packetery_point_id' => '123' ],
					'cartGetCartContents'            => [],
					'isShippingRateRestrictedByProductsCategory' => false,
					'getCarrierIdFromPacketeryShippingMethod' => 'dummyPpId',
					'getChosenPaymentMethod'         => null,
					'isPickupPointOrder'             => true,
					'getCustomerCountry'             => null,
				],
				'shouldThrow'        => true,
			],
			'pickup point carrier not valid'       => [
				'scenarioParameters' => [
					'resolveChosenMethod'            => 'packetery_method',
					'isPacketeryShippingMethod'      => true,
					'getPostDataIncludingStoredData' => [ 'packetery_point_id' => '123' ],
					'cartGetCartContents'            => [],
					'isShippingRateRestrictedByProductsCategory' => false,
					'getCarrierIdFromPacketeryShippingMethod' => 'dummyPpId',
					'getChosenPaymentMethod'         => null,
					'isPickupPointOrder'             => true,
					'getCustomerCountry'             => 'CZ',
					'isValidForCountry'              => false,
				],
				'shouldThrow'        => true,
			],
			'home delivery required not validated' => [
				'scenarioParameters' => [
					'resolveChosenMethod'            => 'packetery_method',
					'isPacketeryShippingMethod'      => true,
					'getPostDataIncludingStoredData' => [],
					'cartGetCartContents'            => [],
					'isShippingRateRestrictedByProductsCategory' => false,
					'getCarrierIdFromPacketeryShippingMethod' => 'dummyHdId',
					'getChosenPaymentMethod'         => null,
					'isPickupPointOrder'             => false,
					'isHomeDeliveryOrder'            => true,
					'getOption'                      => [ 'address_validation' => 'required' ],
				],
				'shouldThrow'        => true,
			],
			'home delivery validated'              => [
				'scenarioParameters' => [
					'resolveChosenMethod'            => 'packetery_method',
					'isPacketeryShippingMethod'      => true,
					'getPostDataIncludingStoredData' => [ 'packetery_address_isValidated' => '1' ],
					'cartGetCartContents'            => [],
					'isShippingRateRestrictedByProductsCategory' => false,
					'getCarrierIdFromPacketeryShippingMethod' => 'dummyHdId',
					'getChosenPaymentMethod'         => null,
					'isPickupPointOrder'             => false,
					'isHomeDeliveryOrder'            => true,
					'getOption'                      => [ 'address_validation' => 'required' ],
				],
				'shouldThrow'        => false,
			],
			'home delivery no options'             => [
				'scenarioParameters' => [
					'resolveChosenMethod'            => 'packetery_method',
					'isPacketeryShippingMethod'      => true,
					'getPostDataIncludingStoredData' => [],
					'cartGetCartContents'            => [],
					'isShippingRateRestrictedByProductsCategory' => false,
					'getCarrierIdFromPacketeryShippingMethod' => 'dummyHdId',
					'getChosenPaymentMethod'         => null,
					'isPickupPointOrder'             => false,
					'isHomeDeliveryOrder'            => true,
					'getOption'                      => false,
				],
				'shouldThrow'        => false,
			],
			'mysterious packetery method'          => [
				'scenarioParameters' => [
					'resolveChosenMethod'       => 'packetery_method',
					'isPacketeryShippingMethod' => true,
					'isPickupPointOrder'        => false,
					'isHomeDeliveryOrder'       => false,
					'getOption'                 => false,
				],
				'shouldThrow'        => false,
			],
		];
	}

	/**
	 * @dataProvider actionValidateBlockCheckoutDataProvider
	 */
	public function testActionValidateBlockCheckoutData( array $scenarioParameters, bool $shouldThrow ): void {
		$checkoutValidator = $this->createCheckoutValidator();

		$this->checkoutService->method( 'resolveChosenMethod' )
			->willReturn( $scenarioParameters['resolveChosenMethod'] );
		$this->checkoutService->method( 'isPacketeryShippingMethod' )
			->willReturn( $scenarioParameters['isPacketeryShippingMethod'] );
		$this->wpAdapter->method( '__' )
			->willReturnCallback(
				static fn( string $text ): string => $text
			);

		if ( isset( $scenarioParameters['getPostDataIncludingStoredData'] ) ) {
			$this->checkoutStorage->method( 'getPostDataIncludingStoredData' )
				->willReturn( $scenarioParameters['getPostDataIncludingStoredData'] );
		}
		if ( isset( $scenarioParameters['cartGetCartContents'] ) ) {
			$this->wcAdapter->method( 'cartGetCartContents' )
				->willReturn( $scenarioParameters['cartGetCartContents'] );
		}
		if ( isset( $scenarioParameters['isShippingRateRestrictedByProductsCategory'] ) ) {
			$this->cartService->method( 'isShippingRateRestrictedByProductsCategory' )
				->willReturn( $scenarioParameters['isShippingRateRestrictedByProductsCategory'] );
		}
		if ( isset( $scenarioParameters['getCarrierIdFromPacketeryShippingMethod'] ) ) {
			$this->checkoutService->method( 'getCarrierIdFromPacketeryShippingMethod' )
				->willReturn( $scenarioParameters['getCarrierIdFromPacketeryShippingMethod'] );
		}
		if ( isset( $scenarioParameters['getChosenPaymentMethod'] ) ) {
			$this->sessionService->method( 'getChosenPaymentMethod' )
				->willReturn( $scenarioParameters['getChosenPaymentMethod'] );
		}
		if ( isset( $scenarioParameters['isPickupPointOrder'] ) ) {
			$this->checkoutService->method( 'isPickupPointOrder' )
				->willReturn( $scenarioParameters['isPickupPointOrder'] );
		}
		if ( isset( $scenarioParameters['isHomeDeliveryOrder'] ) ) {
			$this->checkoutService->method( 'isHomeDeliveryOrder' )
				->willReturn( $scenarioParameters['isHomeDeliveryOrder'] );
		}
		if ( isset( $scenarioParameters['isCarDeliveryOrder'] ) ) {
			$this->checkoutService->method( 'isCarDeliveryOrder' )
				->willReturn( $scenarioParameters['isCarDeliveryOrder'] );
		}
		if ( isset( $scenarioParameters['getCustomerCountry'] ) ) {
			$this->checkoutService->method( 'getCustomerCountry' )
				->willReturn( $scenarioParameters['getCustomerCountry'] );
		}
		if ( isset( $scenarioParameters['isValidForCountry'] ) ) {
			$this->carrierEntityRepository->method( 'isValidForCountry' )
				->willReturn( $scenarioParameters['isValidForCountry'] );
		}
		if ( isset( $scenarioParameters['getOption'] ) ) {
			$this->wpAdapter->method( 'getOption' )
				->willReturn( $scenarioParameters['getOption'] );
		}
		if ( isset( $scenarioParameters['createByCarrierId'] ) ) {
			$this->carrierOptionsFactory->method( 'createByCarrierId' )
				->willReturn( $scenarioParameters['createByCarrierId'] );
		}

		if ( $shouldThrow ) {
			// Throws fake exception from woocommerce-stubs without details.
			$this->expectException( WC_REST_Exception::class );
		} else {
			$this->expectNotToPerformAssertions();
		}

		$checkoutValidator->actionValidateBlockCheckoutData();
	}
}
