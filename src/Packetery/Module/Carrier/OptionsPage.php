<?php
/**
 * Class OptionsPage
 *
 * @package Packetery\Options
 */

declare( strict_types=1 );

namespace Packetery\Module\Carrier;

use Packetery\Core\Entity\Carrier;
use Packetery\Latte\Engine;
use Packetery\Module\Dashboard\DashboardPage;
use Packetery\Module\Forms\CarrierFormFactory;
use Packetery\Module\Forms\ShippingClassFormFactory;
use Packetery\Module\Forms\ShippingFormHelper;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\MessageManager;
use Packetery\Module\ModuleHelper;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Views\UrlBuilder;
use Packetery\Nette\Http\Request;

/**
 * Class OptionsPage
 *
 * @package Packetery\Options
 */
class OptionsPage {

	public const FORM_FIELD_NAME                 = 'name';
	public const FORM_FIELD_ACTIVE               = 'active';
	public const FORM_FIELD_WEIGHT_LIMITS        = 'weight_limits';
	public const FORM_FIELD_PRODUCT_VALUE_LIMITS = 'product_value_limits';
	public const FORM_FIELD_PRICING_TYPE         = 'pricing_type';
	public const FORM_FIELD_MAX_CART_VALUE       = 'max_cart_value';
	public const FORM_FIELD_CLASS_CALC_TYPE      = 'class_calculation_type';
	public const OPTIONS_SECTION_PER_CLASS       = 'per_class';

	public const SLUG                    = 'packeta-country';
	public const PARAMETER_COUNTRY_CODE  = 'country_code';
	public const PARAMETER_CARRIER_ID    = 'carrier_id';
	public const MINIMUM_CHECKED_VENDORS = 2;

	private Engine $latteEngine;
	private EntityRepository $carrierRepository;
	private Request $httpRequest;
	private CountryListingPage $countryListingPage;
	private MessageManager $messageManager;
	private CarDeliveryConfig $carDeliveryConfig;
	private ModuleHelper $moduleHelper;
	private UrlBuilder $urlBuilder;
	private WpAdapter $wpAdapter;
	private WcAdapter $wcAdapter;
	private OptionsProvider $optionsProvider;
	private ShippingClassPage $shippingClassPage;
	private ShippingFormHelper $shippingFormHelper;
	private CarrierFormFactory $carrierFormFactory;
	private ShippingClassFormFactory $shippingClassFormFactory;

	public function __construct(
		Engine $latteEngine,
		EntityRepository $carrierRepository,
		Request $httpRequest,
		CountryListingPage $countryListingPage,
		MessageManager $messageManager,
		CarDeliveryConfig $carDeliveryConfig,
		ModuleHelper $moduleHelper,
		UrlBuilder $urlBuilder,
		WpAdapter $wpAdapter,
		WcAdapter $wcAdapter,
		OptionsProvider $optionsProvider,
		ShippingClassPage $shippingClassPage,
		ShippingFormHelper $shippingFormHelper,
		CarrierFormFactory $carrierFormFactory,
		ShippingClassFormFactory $shippingClassFormFactory
	) {
		$this->latteEngine              = $latteEngine;
		$this->carrierRepository        = $carrierRepository;
		$this->httpRequest              = $httpRequest;
		$this->countryListingPage       = $countryListingPage;
		$this->messageManager           = $messageManager;
		$this->carDeliveryConfig        = $carDeliveryConfig;
		$this->moduleHelper             = $moduleHelper;
		$this->urlBuilder               = $urlBuilder;
		$this->wpAdapter                = $wpAdapter;
		$this->wcAdapter                = $wcAdapter;
		$this->optionsProvider          = $optionsProvider;
		$this->shippingClassPage        = $shippingClassPage;
		$this->shippingFormHelper       = $shippingFormHelper;
		$this->carrierFormFactory       = $carrierFormFactory;
		$this->shippingClassFormFactory = $shippingClassFormFactory;
	}

	/**
	 * Registers WP callbacks.
	 */
	public function register(): void {
		add_submenu_page(
			DashboardPage::SLUG,
			__( 'Carrier settings', 'packeta' ),
			__( 'Carrier settings', 'packeta' ),
			'manage_options',
			self::SLUG,
			array(
				$this,
				'render',
			),
			10
		);
	}

	/**
	 * Gets carrier template params.
	 *
	 * @param Carrier|null $carrier Carrier.
	 *
	 * @return array|null
	 */
	private function getCarrierTemplateData( ?Carrier $carrier ): ?array {
		if ( $carrier === null ) {
			return null;
		}

		if ( $carrier->isCarDelivery() && $this->carDeliveryConfig->isDisabled() ) {
			return null;
		}

		/** @var array<string, string> $post */
		$post = $this->httpRequest->getPost();
		if ( isset( $post['id'] ) && $post['id'] === $carrier->getId() ) {
			$formTemplate = $this->carrierFormFactory->createFormTemplate( $post['id'] );
			$form         = $this->carrierFormFactory->createForm( $post );
			if ( $form->isSubmitted() !== false ) {
				$form->fireEvents();
			}
		} else {
			$carrierData = $carrier->__toArray();
			$options     = get_option( OptionPrefixer::getOptionId( $carrier->getId() ) );
			if ( $options !== false ) {
				$carrierData += $options;
			}
			$formTemplate = $this->carrierFormFactory->createFormTemplate( $carrierData['id'] );
			$form         = $this->carrierFormFactory->createForm( $carrierData );
		}

		if ( isset( $post[ ShippingClassFormFactory::FORM_FIELD_CARRIER_ID ], $post[ ShippingClassFormFactory::FORM_FIELD_CLASS_SLUG ] ) ) {
			$classForm = null;
			foreach ( $this->shippingFormHelper->getShippingClasses() as $shippingClass ) {
				if ( $post[ ShippingClassFormFactory::FORM_FIELD_CLASS_SLUG ] === $shippingClass['slug'] ) {
					$classForm = $this->shippingClassFormFactory->createFromClassAndCarrier(
						$shippingClass,
						$post[ ShippingClassFormFactory::FORM_FIELD_CARRIER_ID ]
					);
				}
			}
			if ( $classForm !== null && $classForm->isSubmitted() !== false ) {
				$classForm->fireEvents();
			}
		}

		return [
			'form'                                 => $form,
			'formTemplate'                         => $formTemplate,
			'carrier'                              => $carrier,
			'couponFreeShippingForFeesContainerId' => $this->carrierFormFactory->createCouponFreeShippingForFeesContainerId( $form ),
			'dimensionRestrictionContainerId'      => $this->carrierFormFactory->createDimensionRestrictionContainerId( $form ),
			'weightLimitsContainerId'              => $this->shippingFormHelper->createFieldContainerId( $form, self::FORM_FIELD_WEIGHT_LIMITS ),
			'productValueLimitsContainerId'        => $this->shippingFormHelper->createFieldContainerId( $form, self::FORM_FIELD_PRODUCT_VALUE_LIMITS ),
			'isAvailableVendorsCountLow'           => $this->isAvailableVendorsCountLowByCarrierId( $carrier->getId() ),
		];
	}

	public function render(): void {
		$countryIso = $this->httpRequest->getQuery( self::PARAMETER_COUNTRY_CODE );
		$carrierId  = $this->httpRequest->getQuery( self::PARAMETER_CARRIER_ID );

		if ( $carrierId !== null ) {
			$this->renderCarrierDetail( $carrierId );
		} elseif ( $countryIso !== null ) {
			$this->renderCountryCarriers( $countryIso );
		} else {
			$this->countryListingPage->render();
		}
	}

	private function renderCarrierDetail( string $carrierId ): void {
		$carrier             = $this->carrierRepository->getAnyById( $carrierId );
		$carrierTemplateData = $this->getCarrierTemplateData( $carrier );

		if ( $carrier === null || $carrierTemplateData === null ) {
			$this->countryListingPage->render();

			return;
		}

		$tabbedTemplateParams = [];
		if ( $this->optionsProvider->isWcCarrierConfigEnabled() && count( $this->shippingFormHelper->getShippingClasses() ) > 0 ) {
			$detailTemplate       = '/template/carrier/tabbedDetail.latte';
			$tabbedTemplateParams = $this->shippingClassPage->getTemplateParams( $carrierId );
		} else {
			$detailTemplate = '/template/carrier/detail.latte';
		}

		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . $detailTemplate,
			array_merge_recursive(
				$this->getCommonTemplateParams(),
				[
					'carrierTemplateData' => $carrierTemplateData,
					'translations'        => [
						'title' => $carrier->getName(),
					],
				],
				$tabbedTemplateParams,
			)
		);
	}

	private function renderCountryCarriers( string $countryIso ): void {
		$countryCarriers = $this->carrierRepository->getByCountryIncludingNonFeed( $countryIso, true );
		$carriersData    = [];
		foreach ( $countryCarriers as $carrier ) {
			$carrierTemplateData = $this->getCarrierTemplateData( $carrier );
			if ( $carrierTemplateData !== null ) {
				$carriersData[] = $carrierTemplateData;
			}
		}

		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/carrier/country.latte',
			array_merge_recursive(
				$this->getCommonTemplateParams(),
				[
					'forms'        => $carriersData,
					'country_iso'  => $countryIso,
					'translations' => [
						'title' => sprintf(
							// translators: %s is country code.
							$this->wpAdapter->__( 'Country options: %s', 'packeta' ),
							strtoupper( $countryIso )
						),
					],
				]
			)
		);
	}

	/**
	 * @return array<string, null|string|bool|array<string, string>>
	 */
	private function getCommonTemplateParams(): array {
		return [
			'globalCurrency' => $this->wcAdapter->getCurrencySymbol(),
			'flashMessages'  => $this->messageManager->renderToString( MessageManager::RENDERER_PACKETERY, 'carrier-country' ),
			'isCzechLocale'  => $this->moduleHelper->isCzechLocale(),
			'logoZasilkovna' => $this->urlBuilder->buildAssetUrl( 'public/images/logo-zasilkovna.svg' ),
			'logoPacketa'    => $this->urlBuilder->buildAssetUrl( 'public/images/logo-packeta.svg' ),
			'translations'   => $this->getTranslations(),
		];
	}

	/**
	 * @return array<string, string>
	 */
	private function getTranslations(): array {
		return [
			'cannotUseThisCarrierBecauseRequiresCustomsDeclaration' => $this->wpAdapter->__( 'This carrier cannot be used, because it requires a customs declaration.', 'packeta' ),
			'delete'                                 => $this->wpAdapter->__( 'Delete', 'packeta' ),
			'weightRules'                            => $this->wpAdapter->__( 'Weight rules', 'packeta' ),
			'productValueRules'                      => $this->wpAdapter->__( 'Product value rules', 'packeta' ),
			'addWeightRule'                          => $this->wpAdapter->__( 'Add weight rule', 'packeta' ),
			'addProductValueRule'                    => $this->wpAdapter->__( 'Add product value rule', 'packeta' ),
			'codSurchargeRules'                      => $this->wpAdapter->__( 'COD surcharge rules', 'packeta' ),
			'addCodSurchargeRule'                    => $this->wpAdapter->__( 'Add COD surcharge rule', 'packeta' ),
			'afterExceedingThisAmountShippingIsFree' => $this->wpAdapter->__( 'After exceeding this amount, shipping is free.', 'packeta' ),
			'daysUntilShipping'                      => $this->wpAdapter->__( 'Number of business days it might take to process an order before shipping out a package.', 'packeta' ),
			'shippingTimeCutOff'                     => $this->wpAdapter->__( 'A time of a day you stop taking in more orders for the next round of shipping.', 'packeta' ),
			'addressValidationDescription'           => $this->wpAdapter->__( 'Customer address validation.', 'packeta' ),
			'roundingDescription'                    => $this->wpAdapter->__( 'COD rounding for submitting data to Packeta', 'packeta' ),
			'saveChanges'                            => $this->wpAdapter->__( 'Save changes', 'packeta' ),
			'packeta'                                => $this->wpAdapter->__( 'Packeta', 'packeta' ),
			'noKnownCarrierForThisCountry'           => $this->wpAdapter->__( 'No carriers available for this country.', 'packeta' ),
			'ageVerificationSupportedNotification'   => $this->wpAdapter->__( 'When shipping via this carrier, you can order the Age Verification service. The service will get ordered automatically if there is at least 1 product in the order with the age verification setting.', 'packeta' ),
			'carrierDoesNotSupportCod'               => $this->wpAdapter->__( 'This carrier does not support COD payment.', 'packeta' ),
			'allowedPickupPointTypes'                => $this->wpAdapter->__( 'Pickup point types', 'packeta' ),
			'checkAtLeastTwo'                        => $this->wpAdapter->__( 'Check at least two types of pickup points or use a carrier which delivers to the desired pickup point type.', 'packeta' ),
			'lowAvailableVendorsCount'               => $this->wpAdapter->__( 'This carrier displays all types of pickup points at the same time in the checkout (retail store pickup points, Z-boxes).', 'packeta' ),
			'carrierUnavailable'                     => $this->wpAdapter->__( 'This carrier is unavailable.', 'packeta' ),
			'maxCartValueDescription'                => $this->wpAdapter->__( 'If the value of all products in the cart exceeds the specified value, Packeta shipping methods will not be available. This setting takes precedence over the max value of products in cart in the plugin general settings. Use the same convention as the Prices include tax setting.', 'packeta' ),
		];
	}

	/**
	 * Checks if the number of vendors is lower than the minimum required by the carrier id
	 *
	 * @param string $carrierId
	 *
	 * @return bool
	 */
	public function isAvailableVendorsCountLowByCarrierId( string $carrierId ): bool {
		$availableVendors = $this->carrierFormFactory->getAvailableVendors( $carrierId );

		return is_array( $availableVendors ) && $this->carrierFormFactory->isAvailableVendorsCountLowerThanRequiredMinimum( $availableVendors );
	}
}
