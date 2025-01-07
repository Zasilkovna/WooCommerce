<?php

declare( strict_types=1 );

namespace Packetery\Module\Checkout;

use DateTime;
use Packetery\Core\Entity;
use Packetery\Module\Api;
use Packetery\Module\Carrier;
use Packetery\Module\Carrier\CarDeliveryConfig;
use Packetery\Module\Carrier\OptionPrefixer;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Order;
use Packetery\Module\Plugin;
use Packetery\Module\Views\UrlBuilder;
use Packetery\Module\WidgetOptionsBuilder;
use WC_Cart;

class CheckoutSettings {

	/**
	 * @var Carrier\EntityRepository
	 */
	private $carrierEntityRepository;

	/**
	 * @var WidgetOptionsBuilder
	 */
	private $widgetOptionsBuilder;

	/**
	 * @var UrlBuilder
	 */
	private $urlBuilder;

	/**
	 * @var CarDeliveryConfig
	 */
	private $carDeliveryConfig;

	/**
	 * @var OptionsProvider
	 */
	private $optionsProvider;

	/**
	 * @var Api\Internal\CheckoutRouter
	 */
	private $apiRouter;

	/**
	 * @var Carrier\CarrierOptionsFactory
	 */
	private $carrierOptionsFactory;

	/**
	 * @var CheckoutStorage
	 */
	private $storage;

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
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * @var WcAdapter
	 */
	private $wcAdapter;

	public function __construct(
		Carrier\EntityRepository $carrierEntityRepository,
		WidgetOptionsBuilder $widgetOptionsBuilder,
		UrlBuilder $urlBuilder,
		CarDeliveryConfig $carDeliveryConfig,
		OptionsProvider $optionsProvider,
		Api\Internal\CheckoutRouter $apiRouter,
		Carrier\CarrierOptionsFactory $carrierOptionsFactory,
		CheckoutStorage $storage,
		CheckoutService $checkoutService,
		CartService $cartService,
		SessionService $sessionService,
		WpAdapter $wpAdapter,
		WcAdapter $wcAdapter
	) {

		$this->carrierEntityRepository = $carrierEntityRepository;
		$this->widgetOptionsBuilder    = $widgetOptionsBuilder;
		$this->urlBuilder              = $urlBuilder;
		$this->carDeliveryConfig       = $carDeliveryConfig;
		$this->optionsProvider         = $optionsProvider;
		$this->apiRouter               = $apiRouter;
		$this->carrierOptionsFactory   = $carrierOptionsFactory;
		$this->storage                 = $storage;
		$this->checkoutService         = $checkoutService;
		$this->cartService             = $cartService;
		$this->sessionService          = $sessionService;
		$this->wpAdapter               = $wpAdapter;
		$this->wcAdapter               = $wcAdapter;
	}

	/**
	 * Creates settings for checkout script.
	 *
	 * @return array
	 */
	public function createSettings(): array {
		if ( ! $this->wcAdapter->cart() instanceof WC_Cart ) {
			return [];
		}

		$carriersConfigForWidget = [];
		$carriers                = $this->carrierEntityRepository->getAllCarriersIncludingNonFeed();

		foreach ( $carriers as $carrier ) {
			$optionId = Carrier\OptionPrefixer::getOptionId( $carrier->getId() );

			$carriersConfigForWidget[ $optionId ] = $this->widgetOptionsBuilder->getCarrierForCheckout(
				$carrier,
				$optionId
			);
		}

		/**
		 * Filter widget weight in checkout.
		 *
		 * @since 1.6.3
		 */
		$widgetWeight = (float) $this->wpAdapter->applyFilters( 'packeta_widget_weight', $this->cartService->getCartWeightKg() );

		/**
		 * Filter widget language in checkout.
		 *
		 * @since 1.4.2
		 */
		$language = (string) $this->wpAdapter->applyFilters( 'packeta_widget_language', substr( get_locale(), 0, 2 ) );

		return [
			'language'                   => $language,
			'logo'                       => $this->urlBuilder->buildAssetUrl( 'public/images/packeta-symbol.png' ),
			'country'                    => $this->checkoutService->getCustomerCountry() ?? '',
			'weight'                     => $widgetWeight,
			'carrierConfig'              => $carriersConfigForWidget,
			'isCarDeliverySampleEnabled' => $this->carDeliveryConfig->isSampleEnabled(),
			'isAgeVerificationRequired'  => $this->cartService->isAgeVerificationRequired(),
			'biggestProductSize'         => $this->cartService->getBiggestProductSize(),
			'pickupPointAttrs'           => Order\Attribute::$pickupPointAttributes,
			'homeDeliveryAttrs'          => Order\Attribute::$homeDeliveryAttributes,
			'carDeliveryAttrs'           => Order\Attribute::$carDeliveryAttributes,
			'carDeliveryCarriers'        => Entity\Carrier::CAR_DELIVERY_CARRIERS,
			'expeditionDay'              => $this->getCarDeliveryExpeditionDay(),
			'appIdentity'                => Plugin::getAppIdentity(),
			'packeteryApiKey'            => $this->optionsProvider->get_api_key(),
			'widgetAutoOpen'             => $this->optionsProvider->shouldWidgetOpenAutomatically(),
			'saveSelectedPickupPointUrl' => $this->apiRouter->getSaveSelectedPickupPointUrl(),
			'saveValidatedAddressUrl'    => $this->apiRouter->getSaveValidatedAddressUrl(),
			'saveCarDeliveryDetailsUrl'  => $this->apiRouter->getSaveCarDeliveryDetailsUrl(),
			'removeSavedDataUrl'         => $this->apiRouter->getRemoveSavedDataUrl(),
			'adminAjaxUrl'               => $this->wpAdapter->adminUrl( 'admin-ajax.php' ),
			'nonce'                      => (string) $this->wpAdapter->createNonce( 'wp_rest' ),
			'savedData'                  => $this->storage->getFromTransient(),
			'translations'               => [
				'packeta'                       => $this->wpAdapter->__( 'Packeta', 'packeta' ),
				'choosePickupPoint'             => $this->wpAdapter->__( 'Choose pickup point', 'packeta' ),
				'pickupPointNotChosen'          => $this->wpAdapter->__( 'Pickup point is not chosen.', 'packeta' ),
				'placeholderText'               => $this->wpAdapter->__( 'Loading Packeta widget button...', 'packeta' ),
				'chooseAddress'                 => $this->wpAdapter->__( 'Choose delivery address', 'packeta' ),
				'addressValidationIsOutOfOrder' => $this->wpAdapter->__( 'Address validation is out of order', 'packeta' ),
				'invalidAddressCountrySelected' => $this->wpAdapter->__( 'The selected country does not correspond to the destination country.', 'packeta' ),
				'deliveryAddressNotification'   => $this->wpAdapter->__( 'The order will be delivered to the address:', 'packeta' ),
				'addressIsNotValidatedAndRequiredByCarrier' => $this->wpAdapter->__( 'Delivery address has not been chosen. Choosing a delivery address using the widget is required by this carrier.', 'packeta' ),
			],
		];
	}

	/**
	 * Used to provide additional settings for blocks checkout.
	 *
	 * @return void
	 */
	public function actionCreateSettingsAjax(): void {
		$settings = [];
		if ( $this->wcAdapter->cart() instanceof WC_Cart ) {
			$settings['biggestProductSize']        = $this->cartService->getBiggestProductSize();
			$settings['isAgeVerificationRequired'] = $this->cartService->isAgeVerificationRequired();
		}

		$this->wpAdapter->sendJson( $settings );
	}

	/**
	 * Calculates and returns Expedition Day
	 *
	 * @return string|null
	 */
	private function getCarDeliveryExpeditionDay(): ?string {
		$chosenShippingMethod = $this->sessionService->getChosenMethodFromSession();
		$carrierId            = OptionPrefixer::removePrefix( $chosenShippingMethod );
		if ( $this->carDeliveryConfig->isCarDeliveryCarrier( $carrierId ) === false ) {
			return null;
		}

		$carrierOptions = $this->carrierOptionsFactory->createByOptionId( $chosenShippingMethod )->toArray();
		$today          = new DateTime();
		$processingDays = $carrierOptions['days_until_shipping'];
		$cutoffTime     = $carrierOptions['shipping_time_cut_off'];

		// Check if a cut-off time is provided and if the current time is after the cut-off time.
		if ( $cutoffTime !== null ) {
			$currentTime = $today->format( 'H:i' );
			if ( $currentTime > $cutoffTime ) {
				// If after cut-off time, move to the next day.
				$today->modify( '+1 day' );
			}
		}

		// Loop through each day to add processing days, skipping weekends.
		for ( $i = 0; $i < $processingDays; $i++ ) {
			// Add a day to the current date.
			$today->modify( '+1 day' );

			// Check if the current day is a weekend (Saturday or Sunday).
			if ( $today->format( 'N' ) >= 6 ) {
				// If it's a weekend, move to the next Monday.
				$today->modify( 'next Monday' );
			}
		}

		// Get the final expedition day.
		return $today->format( 'Y-m-d' );
	}
}
