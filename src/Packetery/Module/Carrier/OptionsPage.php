<?php
/**
 * Class OptionsPage
 *
 * @package Packetery\Options
 */

declare( strict_types=1 );

namespace Packetery\Module\Carrier;

use Packetery\Core\Entity\Carrier;
use Packetery\Core\CoreHelper;
use Packetery\Core\Rounder;
use Packetery\Latte\Engine;
use Packetery\Module\FormFactory;
use Packetery\Module\FormValidators;
use Packetery\Module\MessageManager;
use Packetery\Module\Options\FlagManager\FeatureFlagProvider;
use Packetery\Module\PaymentGatewayHelper;
use Packetery\Nette\Forms\Container;
use Packetery\Nette\Forms\Control;
use Packetery\Nette\Forms\Form;
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

	public const SLUG                    = 'packeta-country';
	public const PARAMETER_COUNTRY_CODE  = 'country_code';
	public const PARAMETER_CARRIER_ID    = 'carrier_id';
	public const MINIMUM_CHECKED_VENDORS = 2;

	/**
	 * PacketeryLatte_engine.
	 *
	 * @var Engine PacketeryLatte engine.
	 */
	private $latteEngine;

	/**
	 * Carrier repository.
	 *
	 * @var EntityRepository Carrier repository.
	 */
	private $carrierRepository;

	/**
	 * Form factory.
	 *
	 * @var FormFactory Form factory.
	 */
	private $formFactory;

	/**
	 * Packetery\Nette Request.
	 *
	 * @var Request Packetery\Nette Request.
	 */
	private $httpRequest;

	/**
	 * CountryListingPage.
	 *
	 * @var CountryListingPage CountryListingPage.
	 */
	private $countryListingPage;

	/**
	 * Message manager.
	 *
	 * @var MessageManager
	 */
	private $messageManager;

	/**
	 * Internal pickup points config.
	 *
	 * @var PacketaPickupPointsConfig
	 */
	private $pickupPointsConfig;

	/**
	 * Feature flag.
	 *
	 * @var FeatureFlagProvider
	 */
	private $featureFlagProvider;

	/**
	 * Car delivery config.
	 *
	 * @var CarDeliveryConfig
	 */
	private $carDeliveryConfig;

	/**
	 * WC Native carrier settings config.
	 *
	 * @var WcSettingsConfig
	 */
	private $wcSettingsConfig;

	/**
	 * Carrier options factory.
	 *
	 * @var CarrierOptionsFactory
	 */
	private $carrierOptionsFactory;

	/**
	 * OptionsPage constructor.
	 *
	 * @param Engine                    $latteEngine           PacketeryLatte_engine.
	 * @param EntityRepository          $carrierRepository     Carrier repository.
	 * @param FormFactory               $formFactory           Form factory.
	 * @param Request                   $httpRequest           Packetery\Nette Request.
	 * @param CountryListingPage        $countryListingPage    CountryListingPage.
	 * @param MessageManager            $messageManager        Message manager.
	 * @param PacketaPickupPointsConfig $pickupPointsConfig    Internal pickup points config.
	 * @param FeatureFlagProvider       $featureFlagProvider   Feature flag.
	 * @param CarDeliveryConfig         $carDeliveryConfig     Car delivery config.
	 * @param WcSettingsConfig          $wcSettingsConfig      WC Native carrier settings config.
	 * @param CarrierOptionsFactory     $carrierOptionsFactory Carrier options factory.
	 */
	public function __construct(
		Engine $latteEngine,
		EntityRepository $carrierRepository,
		FormFactory $formFactory,
		Request $httpRequest,
		CountryListingPage $countryListingPage,
		MessageManager $messageManager,
		PacketaPickupPointsConfig $pickupPointsConfig,
		FeatureFlagProvider $featureFlagProvider,
		CarDeliveryConfig $carDeliveryConfig,
		WcSettingsConfig $wcSettingsConfig,
		CarrierOptionsFactory $carrierOptionsFactory
	) {
		$this->latteEngine           = $latteEngine;
		$this->carrierRepository     = $carrierRepository;
		$this->formFactory           = $formFactory;
		$this->httpRequest           = $httpRequest;
		$this->countryListingPage    = $countryListingPage;
		$this->messageManager        = $messageManager;
		$this->pickupPointsConfig    = $pickupPointsConfig;
		$this->featureFlagProvider   = $featureFlagProvider;
		$this->carDeliveryConfig     = $carDeliveryConfig;
		$this->wcSettingsConfig      = $wcSettingsConfig;
		$this->carrierOptionsFactory = $carrierOptionsFactory;
	}

	/**
	 * Registers WP callbacks.
	 */
	public function register(): void {
		add_submenu_page(
			\Packetery\Module\Options\Page::SLUG,
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
	 * Creates settings form.
	 *
	 * @param array $carrierData Carrier data.
	 *
	 * @return Form
	 */
	private function createForm( array $carrierData ): Form {
		$optionId = OptionPrefixer::getOptionId( $carrierData['id'] );

		$form = $this->formFactory->create( $optionId );

		if ( false === $this->wcSettingsConfig->isActive() ) {
			$form->addCheckbox(
				self::FORM_FIELD_ACTIVE,
				__( 'Active carrier', 'packeta' ) . ':'
			);
		}

		$form->addText( self::FORM_FIELD_NAME, __( 'Display name', 'packeta' ) . ':' )
			->setRequired();

		$carrierOptions = get_option( $optionId );
		if ( $this->featureFlagProvider->isSplitActive() ) {
			$vendorCheckboxes = $this->getVendorCheckboxesConfig( $carrierData['id'], ( $carrierOptions ? $carrierOptions : null ) );
			if ( $vendorCheckboxes ) {
				$vendorsContainer = $form->addContainer( 'vendor_groups' );
				foreach ( $vendorCheckboxes as $checkboxConfig ) {
					$checkboxControl = $vendorsContainer->addCheckbox( $checkboxConfig['group'], $checkboxConfig['name'] );
					if ( true === $checkboxConfig['disabled'] ) {
						$checkboxControl->setDisabled()->setOmitted( false );
					}
					if ( true === $checkboxConfig['default'] ) {
						$checkboxControl->setDefaultValue( true );
					}
				}
			}
		}

		$form->addSelect(
			self::FORM_FIELD_PRICING_TYPE,
			__( 'Pricing type', 'packeta' ),
			[
				Options::PRICING_TYPE_BY_WEIGHT        => __( 'By weight', 'packeta' ),
				Options::PRICING_TYPE_BY_PRODUCT_VALUE => __( 'By product value', 'packeta' ),
			]
		)
			->setDefaultValue( Options::PRICING_TYPE_BY_WEIGHT )
			->addCondition( Form::EQUAL, Options::PRICING_TYPE_BY_WEIGHT )
				->toggle( $this->createFieldContainerId( $form, self::FORM_FIELD_WEIGHT_LIMITS ) )
			->endCondition()
			->addCondition( Form::EQUAL, Options::PRICING_TYPE_BY_PRODUCT_VALUE )
				->toggle( $this->createFieldContainerId( $form, self::FORM_FIELD_PRODUCT_VALUE_LIMITS ) );

		$weightLimits = $form->addContainer( self::FORM_FIELD_WEIGHT_LIMITS );
		if ( empty( $carrierData[ self::FORM_FIELD_WEIGHT_LIMITS ] ) ) {
			$this->addWeightLimit( $weightLimits, 0 );
		} else {
			foreach ( $carrierData[ self::FORM_FIELD_WEIGHT_LIMITS ] as $index => $limit ) {
				$this->addWeightLimit( $weightLimits, $index );
			}
		}

		$productValueLimits = $form->addContainer( self::FORM_FIELD_PRODUCT_VALUE_LIMITS );
		if ( empty( $carrierData[ self::FORM_FIELD_PRODUCT_VALUE_LIMITS ] ) ) {
			$this->addProductValueLimit( $productValueLimits, 0 );
		} else {
			foreach ( $carrierData[ self::FORM_FIELD_PRODUCT_VALUE_LIMITS ] as $index => $limit ) {
				$this->addProductValueLimit( $productValueLimits, $index );
			}
		}

		// We don't expect id to be empty in this situation. This would indicate a data save error.
		$carrier = $this->carrierRepository->getAnyById( (string) $carrierData['id'] );

		if ( null !== $carrier && $carrier->supportsCod() ) {
			$form->addText( 'default_COD_surcharge', __( 'Default COD surcharge', 'packeta' ) . ':' )
				->setRequired( false )
				->addRule( Form::FLOAT )
				->addRule( Form::MIN, null, 0 );

			$surchargeLimits = $form->addContainer( 'surcharge_limits' );
			if ( ! empty( $carrierData['surcharge_limits'] ) ) {
				foreach ( $carrierData['surcharge_limits'] as $index => $limit ) {
					$this->addSurchargeLimit( $surchargeLimits, $index );
				}
			}
			$roundingOptions = [
				Rounder::DONT_ROUND => __( 'No rounding', 'packeta' ),
				Rounder::ROUND_DOWN => __( 'Always round down', 'packeta' ),
				Rounder::ROUND_UP   => __( 'Always round up', 'packeta' ),
			];
			$form->addSelect( 'cod_rounding', __( 'COD rounding', 'packeta' ) . ':', $roundingOptions )
				->setDefaultValue( Rounder::DONT_ROUND );
		}

		$item = $form->addText( 'free_shipping_limit', __( 'Free shipping limit', 'packeta' ) . ':' );
		$item->addRule( $form::FLOAT, __( 'Please enter a valid decimal number.', 'packeta' ) );

		if ( $carrier->isCarDelivery() ) {
			$daysUntilShipping = $form->addText( 'days_until_shipping', __( 'Number of days until shipping', 'packeta' ) . ':' );
			$daysUntilShipping->setRequired()
				->addRule( $form::INTEGER, __( 'Please, enter a full number.', 'packeta' ) )
				->addRule( $form::MIN, null, 0 );

			$shippingTimeCutOff = $form->addText( 'shipping_time_cut_off', __( 'Shipping time cut off', 'packeta' ) . ':' );
			$shippingTimeCutOff->setHtmlAttribute( 'class', 'date-picker' )
				->setHtmlType( 'time' )
				->setRequired( false )
				->setNullable()
				// translators: %s: Represents the time we stop taking more orders for next shipment.
				->addRule( [ FormValidators::class, 'hasClockTimeFormat' ], __( 'Time must be between %1$s and %2$s.', 'packeta' ), [ '00:00', '23:59' ] );
		}

		$couponFreeShipping = $form->addContainer( 'coupon_free_shipping' );
		$couponFreeShipping->addCheckbox( 'active', __( 'Apply free shipping coupon', 'packeta' ) );
		$couponFreeShipping->addCheckbox( 'allow_for_fees', __( 'Apply free shipping coupon for fees', 'packeta' ) )
							->addConditionOn( $form['coupon_free_shipping']['active'], Form::FILLED )
							->toggle( $this->createCouponFreeShippingForFeesContainerId( $form ) );

		$form->addHidden( 'id' )->setRequired();
		$form->addSubmit( 'save' );

		if (
			false === $carrier->isCarDelivery() &&
			false === $carrier->hasPickupPoints() &&
			in_array( $carrier->getCountry(), Carrier::ADDRESS_VALIDATION_COUNTRIES, true )
		) {
			$addressValidationOptions = [
				'none'     => __( 'No address validation', 'packeta' ),
				'optional' => __( 'Optional address validation', 'packeta' ),
				'required' => __( 'Required address validation', 'packeta' ),
			];
			$form->addSelect( 'address_validation', __( 'Address validation', 'packeta' ) . ':', $addressValidationOptions )
				->setDefaultValue( 'none' );
		}

		if ( $carrier->supportsAgeVerification() ) {
			$form->addText( 'age_verification_fee', __( 'Age verification fee', 'packeta' ) . ':' )
				->setRequired( false )
				->addRule( Form::FLOAT )
				->addRule( Form::MIN, null, 0 );
		}

		$form->addMultiSelect(
			'disallowed_checkout_payment_methods',
			__( 'Disallowed checkout payment methods', 'packeta' ),
			PaymentGatewayHelper::getAvailablePaymentGatewayChoices()
		)->checkDefaultValue( false );

		$form->onValidate[] = [ $this, 'validateOptions' ];
		$form->onSuccess[]  = [ $this, 'updateOptions' ];

		if ( false === $carrierOptions ) {
			$carrierOptions = [
				'id'                  => $carrierData['id'],
				self::FORM_FIELD_NAME => $carrierData['name'],
			];
		}
		$form->setDefaults( $carrierOptions );

		return $form;
	}

	/**
	 * Creates form toggle ID for coupon free shipping.
	 *
	 * @param Form $form Form.
	 *
	 * @return string
	 */
	private function createCouponFreeShippingForFeesContainerId( Form $form ): string {
		return sprintf( '%s_apply_free_shipping_coupon_allow_for_fees', $form->getName() );
	}

	/**
	 * Creates container id for given field.
	 *
	 * @param Form   $form Form.
	 * @param string $field Field name.
	 *
	 * @return string
	 */
	private function createFieldContainerId( Form $form, string $field ): string {
		return sprintf( '%s_%s_containerId', $form->getName(), $field );
	}

	/**
	 * Creates settings form.
	 *
	 * @param array $carrierData Carrier data.
	 *
	 * @return Form
	 */
	private function createFormTemplate( array $carrierData ): Form {
		$optionId = OptionPrefixer::getOptionId( $carrierData['id'] );

		$form = $this->formFactory->create( $optionId . '_template' );

		$form->addSelect( self::FORM_FIELD_PRICING_TYPE );

		$weightLimitsTemplate = $form->addContainer( self::FORM_FIELD_WEIGHT_LIMITS );
		$this->addWeightLimit( $weightLimitsTemplate, 0 );

		$valueLimitsTemplate = $form->addContainer( self::FORM_FIELD_PRODUCT_VALUE_LIMITS );
		$this->addProductValueLimit( $valueLimitsTemplate, 0 );

		$surchargeLimitsTemplate = $form->addContainer( 'surcharge_limits' );
		$this->addSurchargeLimit( $surchargeLimitsTemplate, 0 );

		return $form;
	}

	/**
	 * Validates options.
	 *
	 * @param Form $form Form.
	 */
	public function validateOptions( Form $form ): void {
		if ( $form->hasErrors() ) {
			add_settings_error( '', '', esc_attr( __( 'Some carrier data is invalid', 'packeta' ) ) );
			return;
		}

		$options = $form->getValues( 'array' );

		if ( $this->featureFlagProvider->isSplitActive() ) {
			$checkedVendors = $this->getCheckedVendors( $options );
			if (
				isset( $options['vendor_groups'] ) &&
				count( $options['vendor_groups'] ) >= self::MINIMUM_CHECKED_VENDORS &&
				count( $checkedVendors ) < self::MINIMUM_CHECKED_VENDORS
			) {
				$vendorMessage = __( 'Check at least two types of pickup points or use a carrier which delivers to the desired pickup point type.', 'packeta' );
				add_settings_error( 'vendor_groups', 'vendor_groups', esc_attr( $vendorMessage ) );
				$form->addError( $vendorMessage );
			}
		}

		if ( Options::PRICING_TYPE_BY_WEIGHT === $options[ self::FORM_FIELD_PRICING_TYPE ] ) {
			$this->checkOverlapping(
				$form,
				$options,
				self::FORM_FIELD_WEIGHT_LIMITS,
				'weight',
				__( 'Weight rules are overlapping, please fix them.', 'packeta' )
			);
		}

		if ( Options::PRICING_TYPE_BY_PRODUCT_VALUE === $options[ self::FORM_FIELD_PRICING_TYPE ] ) {
			$this->checkOverlapping(
				$form,
				$options,
				self::FORM_FIELD_PRODUCT_VALUE_LIMITS,
				'price',
				__( 'Product price rules are overlapping, please fix them.', 'packeta' )
			);
		}

		if ( isset( $options['surcharge_limits'] ) ) {
			$this->checkOverlapping(
				$form,
				$options,
				'surcharge_limits',
				'order_price',
				__( 'Surcharge rules are overlapping, please fix them.', 'packeta' )
			);
		}
	}

	/**
	 * Saves carrier options. onSuccess callback.
	 *
	 * @param Form $form Form.
	 *
	 * @return void
	 */
	public function updateOptions( Form $form ): void {
		$options    = $form->getValues( 'array' );
		$newVendors = $this->getCheckedVendors( $options );
		if ( $newVendors ) {
			$options['vendor_groups'] = $newVendors;
		}

		$persistedOptions      = $this->carrierOptionsFactory->createByCarrierId( $options['id'] );
		$persistedOptionsArray = $persistedOptions->toArray();
		if ( $this->wcSettingsConfig->isActive() ) {
			$options[ self::FORM_FIELD_ACTIVE ] = $persistedOptions->isActive();
		}

		if ( Options::PRICING_TYPE_BY_WEIGHT === $options[ self::FORM_FIELD_PRICING_TYPE ] ) {
			$options = $this->mergeNewLimits( $options, self::FORM_FIELD_WEIGHT_LIMITS );
			$options = $this->sortLimits( $options, self::FORM_FIELD_WEIGHT_LIMITS, 'weight' );

			$options[ self::FORM_FIELD_PRODUCT_VALUE_LIMITS ] = $persistedOptionsArray[ self::FORM_FIELD_PRODUCT_VALUE_LIMITS ] ?? [];
		}

		if ( Options::PRICING_TYPE_BY_PRODUCT_VALUE === $options[ self::FORM_FIELD_PRICING_TYPE ] ) {
			$options = $this->mergeNewLimits( $options, self::FORM_FIELD_PRODUCT_VALUE_LIMITS );
			$options = $this->sortLimits( $options, self::FORM_FIELD_PRODUCT_VALUE_LIMITS, 'value' );

			$options[ self::FORM_FIELD_WEIGHT_LIMITS ] = $persistedOptionsArray[ self::FORM_FIELD_WEIGHT_LIMITS ] ?? [];
		}

		if ( isset( $options['surcharge_limits'] ) ) {
			$options = $this->mergeNewLimits( $options, 'surcharge_limits' );
			$options = $this->sortLimits( $options, 'surcharge_limits', 'order_price' );
		}

		update_option( OptionPrefixer::getOptionId( $options['id'] ), $options );
		$this->messageManager->flash_message( __( 'Settings saved', 'packeta' ), MessageManager::TYPE_SUCCESS, MessageManager::RENDERER_PACKETERY, 'carrier-country' );

		if ( wp_safe_redirect(
			$this->createUrl(
				$this->httpRequest->getQuery( self::PARAMETER_COUNTRY_CODE ),
				$this->httpRequest->getQuery( self::PARAMETER_CARRIER_ID )
			),
			303
		) ) {
			exit;
		}
	}

	/**
	 * Gets carrier template params.
	 *
	 * @param Carrier|null $carrier Carrier.
	 *
	 * @return array|null
	 */
	private function getCarrierTemplateData( ?Carrier $carrier ): ?array {
		if ( null === $carrier ) {
			return null;
		}

		if ( $carrier->isCarDelivery() && $this->carDeliveryConfig->isDisabled() ) {
			return null;
		}

		$post = $this->httpRequest->getPost();
		if ( ! empty( $post ) && $post['id'] === $carrier->getId() ) {
			$formTemplate = $this->createFormTemplate( $post );
			$form         = $this->createForm( $post );
			if ( $form->isSubmitted() ) {
				$form->fireEvents();
			}
		} else {
			$carrierData = $carrier->__toArray();
			$options     = get_option( OptionPrefixer::getOptionId( $carrier->getId() ) );
			if ( false !== $options ) {
				$carrierData += $options;
			}
			$formTemplate = $this->createFormTemplate( $carrierData );
			$form         = $this->createForm( $carrierData );
		}

		return [
			'form'                                 => $form,
			'formTemplate'                         => $formTemplate,
			'carrier'                              => $carrier,
			'couponFreeShippingForFeesContainerId' => $this->createCouponFreeShippingForFeesContainerId( $form ),
			'weightLimitsContainerId'              => $this->createFieldContainerId( $form, self::FORM_FIELD_WEIGHT_LIMITS ),
			'productValueLimitsContainerId'        => $this->createFieldContainerId( $form, self::FORM_FIELD_PRODUCT_VALUE_LIMITS ),
			'isAvailableVendorsCountLow'           => $this->isAvailableVendorsCountLowByCarrierId( $carrier->getId() ),
		];
	}

	/**
	 *  Renders page.
	 */
	public function render(): void {
		$countryIso           = $this->httpRequest->getQuery( self::PARAMETER_COUNTRY_CODE );
		$carrierId            = $this->httpRequest->getQuery( self::PARAMETER_CARRIER_ID );
		$commonTemplateParams = [
			'globalCurrency' => get_woocommerce_currency_symbol(),
			'flashMessages'  => $this->messageManager->renderToString( MessageManager::RENDERER_PACKETERY, 'carrier-country' ),
			'translations'   => [
				'cannotUseThisCarrierBecauseRequiresCustomsDeclaration' => __( 'This carrier cannot be used, because it requires a customs declaration.', 'packeta' ),
				'delete'                                 => __( 'Delete', 'packeta' ),
				'weightRules'                            => __( 'Weight rules', 'packeta' ),
				'productValueRules'                      => __( 'Product value rules', 'packeta' ),
				'addWeightRule'                          => __( 'Add weight rule', 'packeta' ),
				'addProductValueRule'                    => __( 'Add product value rule', 'packeta' ),
				'codSurchargeRules'                      => __( 'COD surcharge rules', 'packeta' ),
				'addCodSurchargeRule'                    => __( 'Add COD surcharge rule', 'packeta' ),
				'afterExceedingThisAmountShippingIsFree' => __( 'After exceeding this amount, shipping is free.', 'packeta' ),
				'daysUntilShipping'                      => __( 'Number of business days it might take to process an order before shipping out a package.', 'packeta' ),
				'shippingTimeCutOff'                     => __( 'A time of a day you stop taking in more orders for the next round of shipping.', 'packeta' ),
				'addressValidationDescription'           => __( 'Customer address validation.', 'packeta' ),
				'roundingDescription'                    => __( 'COD rounding for submitting data to Packeta', 'packeta' ),
				'saveChanges'                            => __( 'Save changes', 'packeta' ),
				'packeta'                                => __( 'Packeta', 'packeta' ),
				'noKnownCarrierForThisCountry'           => __( 'No carriers available for this country.', 'packeta' ),
				'ageVerificationSupportedNotification'   => __( 'When shipping via this carrier, you can order the Age Verification service. The service will get ordered automatically if there is at least 1 product in the order with the age verification setting.', 'packeta' ),
				'carrierDoesNotSupportCod'               => __( 'This carrier does not support COD payment.', 'packeta' ),
				'allowedPickupPointTypes'                => __( 'Pickup point types', 'packeta' ),
				'checkAtLeastTwo'                        => __( 'Check at least two types of pickup points or use a carrier which delivers to the desired pickup point type.', 'packeta' ),
				'lowAvailableVendorsCount'               => __( 'This carrier displays all types of pickup points at the same time in the checkout (retail store pickup points, Z-boxes).', 'packeta' ),
			],
		];

		if ( $carrierId ) {
			$carrier             = $this->carrierRepository->getAnyById( $carrierId );
			$carrierTemplateData = $this->getCarrierTemplateData( $carrier );
			if ( null === $carrier || null === $carrierTemplateData ) {
				$this->countryListingPage->render();

				return;
			}

			$this->latteEngine->render(
				PACKETERY_PLUGIN_DIR . '/template/carrier/detail.latte',
				array_merge_recursive(
					$commonTemplateParams,
					[
						'carrierTemplateData' => $carrierTemplateData,
						'translations'        => [
							'title' => $carrier->getName(),
						],
					]
				)
			);

		} elseif ( $countryIso ) {
			$countryCarriers = $this->carrierRepository->getByCountryIncludingNonFeed( $countryIso );
			$carriersData    = [];
			foreach ( $countryCarriers as $carrier ) {
				$carrierTemplateData = $this->getCarrierTemplateData( $carrier );
				if ( null !== $carrierTemplateData ) {
					$carriersData[] = $carrierTemplateData;
				}
			}

			$this->latteEngine->render(
				PACKETERY_PLUGIN_DIR . '/template/carrier/country.latte',
				array_merge_recursive(
					$commonTemplateParams,
					[
						'forms'        => $carriersData,
						'country_iso'  => $countryIso,
						'translations' => [
							// translators: %s is country code.
							'title' => sprintf( __( 'Country options: %s', 'packeta' ), strtoupper( $countryIso ) ),

						],
					]
				)
			);
		} else {
			$this->countryListingPage->render();
		}
	}

	/**
	 * Transforms new_ keys to common numeric.
	 *
	 * @param array  $options Options to merge.
	 * @param string $limitsContainer Container id.
	 *
	 * @return array
	 */
	private function mergeNewLimits( array $options, string $limitsContainer ): array {
		$newOptions = [];
		if ( isset( $options[ $limitsContainer ] ) ) {
			foreach ( $options[ $limitsContainer ] as $key => $option ) {
				if ( is_int( $key ) ) {
					$newOptions[ $key ] = $option;
				}
				if ( 0 === strpos( (string) $key, 'new_' ) ) {
					$newOptions[] = $option;
				}
			}
			$options[ $limitsContainer ] = $newOptions;
		}

		return $options;
	}

	/**
	 * Checks rules overlapping.
	 *
	 * @param Form   $form Form.
	 * @param array  $options Form data.
	 * @param string $limitsContainer Container id.
	 * @param string $limitKey Rule id.
	 * @param string $overlappingMessage Error message.
	 *
	 * @return void
	 */
	private function checkOverlapping( Form $form, array $options, string $limitsContainer, string $limitKey, string $overlappingMessage ): void {
		$limits = array_column( $options[ $limitsContainer ], $limitKey );
		if ( count( array_unique( $limits, SORT_NUMERIC ) ) !== count( $limits ) ) {
			add_settings_error( $limitsContainer, $limitsContainer, esc_attr( $overlappingMessage ) );
			$form->addError( $overlappingMessage );
		}
	}

	/**
	 * Sorts rules.
	 *
	 * @param array  $options Form data.
	 * @param string $limitsContainer Container id.
	 * @param string $limitKey Rule id.
	 *
	 * @return array
	 */
	private function sortLimits( array $options, string $limitsContainer, string $limitKey ): array {
		$limits = array_column( $options[ $limitsContainer ], $limitKey );
		array_multisort( $limits, SORT_ASC, $options[ $limitsContainer ] );

		return $options;
	}

	/**
	 * Adds limit fields to form.
	 *
	 * @param Container  $weightLimits Container.
	 * @param int|string $index Index.
	 *
	 * @return void
	 */
	private function addWeightLimit( Container $weightLimits, $index ): void {
		/**
		 * Pricing type control.
		 *
		 * @var Control $pricingTypeControl
		 */
		$pricingTypeControl = $weightLimits->getForm()->getComponent( self::FORM_FIELD_PRICING_TYPE );
		$limit              = $weightLimits->addContainer( (string) $index );
		$item               = $limit->addText( 'weight', __( 'Weight up to', 'packeta' ) . ':' );
		$itemRules          = $item->addConditionOn( $pricingTypeControl, Form::EQUAL, Options::PRICING_TYPE_BY_WEIGHT );
		$itemRules->setRequired();
		$itemRules->addRule( Form::FLOAT, __( 'Please enter a valid decimal number.', 'packeta' ) );
		// translators: %d is numeric threshold.
		$itemRules->addRule( [ FormValidators::class, 'greaterThan' ], __( 'Enter number greater than %d', 'packeta' ), 0.0 );

		$itemRules->addFilter(
			function ( float $value ) {
				return CoreHelper::simplifyWeight( $value );
			}
		);
		// translators: %d is numeric threshold.
		$itemRules->addRule( [ FormValidators::class, 'greaterThan' ], __( 'Enter number greater than %d', 'packeta' ), 0.0 );

		$item      = $limit->addText( 'price', __( 'Price', 'packeta' ) . ':' );
		$itemRules = $item->addConditionOn( $pricingTypeControl, Form::EQUAL, Options::PRICING_TYPE_BY_WEIGHT );
		$itemRules->setRequired();
		$itemRules->addRule( Form::FLOAT, __( 'Please enter a valid decimal number.', 'packeta' ) );
		$itemRules->addRule( Form::MIN, null, 0 );
	}

	/**
	 * Adds product value limit fields to form.
	 *
	 * @param Container  $productValueLimits Container.
	 * @param int|string $index Index.
	 *
	 * @return void
	 */
	private function addProductValueLimit( Container $productValueLimits, $index ): void {
		/** Pricing type control. @var Control $pricingTypeControl */
		$pricingTypeControl = $productValueLimits->getForm()->getComponent( self::FORM_FIELD_PRICING_TYPE );
		$limit              = $productValueLimits->addContainer( (string) $index );
		$item               = $limit->addText( 'value', __( 'Product value up to', 'packeta' ) . ':' );
		$itemRules          = $item->addConditionOn( $pricingTypeControl, Form::EQUAL, Options::PRICING_TYPE_BY_PRODUCT_VALUE );
		$itemRules->setRequired();
		$itemRules->addRule( Form::FLOAT, __( 'Please enter a valid decimal number.', 'packeta' ) );
		$itemRules->addRule( Form::MIN, null, 0 );

		$item      = $limit->addText( 'price', __( 'Price', 'packeta' ) . ':' );
		$itemRules = $item->addConditionOn( $pricingTypeControl, Form::EQUAL, Options::PRICING_TYPE_BY_PRODUCT_VALUE );
		$itemRules->setRequired();
		$itemRules->addRule( Form::FLOAT, __( 'Please enter a valid decimal number.', 'packeta' ) );
		$itemRules->addRule( Form::MIN, null, 0 );
	}

	/**
	 * Adds limit fields to form.
	 *
	 * @param Container  $surchargeLimits Container.
	 * @param int|string $index Index.
	 *
	 * @return void
	 */
	private function addSurchargeLimit( Container $surchargeLimits, $index ): void {
		$limit = $surchargeLimits->addContainer( (string) $index );
		$item  = $limit->addText( 'order_price', __( 'Order price up to', 'packeta' ) . ':' );
		$item->setRequired();
		$item->addRule( Form::FLOAT, __( 'Please enter a valid decimal number.', 'packeta' ) );
		$item->addRule( Form::MIN, null, 0 );
		$item->addCondition( Form::MAX, 0 )
			->addCondition( Form::MIN, 0 )
			// translators: %d is the value.
			->addRule( Form::BLANK, __( 'Value must not be %d', 'packeta' ), 0 );

		$item = $limit->addText( 'surcharge', __( 'Surcharge', 'packeta' ) . ':' );
		$item->setRequired();
		$item->addRule( Form::FLOAT, __( 'Please enter a valid decimal number.', 'packeta' ) );
		$item->addRule( Form::MIN, null, 0 );
	}

	/**
	 * Creates link to page.
	 *
	 * @param string|null $countryCode Country code.
	 * @param string|null $carrierId   Carrier ID.
	 *
	 * @return string
	 */
	public function createUrl( ?string $countryCode = null, ?string $carrierId = null ): string {
		$params = [
			'page' => self::SLUG,
		];

		if ( null !== $countryCode ) {
			$params[ self::PARAMETER_COUNTRY_CODE ] = $countryCode;
		}

		if ( null !== $carrierId ) {
			$params[ self::PARAMETER_CARRIER_ID ] = $carrierId;
		}

		return add_query_arg(
			$params,
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Gets checked vendors.
	 *
	 * @param array $options Form options.
	 *
	 * @return array
	 */
	private function getCheckedVendors( array $options ): array {
		$vendorCodes = [];
		if ( ! empty( $options['vendor_groups'] ) ) {
			$vendorCodes = $options['vendor_groups'];
		}

		$newVendors = [];
		foreach ( $vendorCodes as $vendorId => $isChecked ) {
			if ( ! $isChecked ) {
				continue;
			}
			$newVendors[] = $vendorId;
		}

		return $newVendors;
	}

	/**
	 * Gets available vendors for compound carrier.
	 *
	 * @param string $id Compound carrier id.
	 *
	 * @return array|null
	 */
	private function getAvailableVendors( $id ): ?array {
		if ( ! $this->pickupPointsConfig->isCompoundCarrierId( $id ) ) {
			return null;
		}

		$compoundCarriers = $this->pickupPointsConfig->getCompoundCarriers();
		foreach ( $compoundCarriers as $compoundCarrier ) {
			if ( $id === $compoundCarrier->getId() ) {
				return $compoundCarrier->getVendorCodes();
			}
		}

		return null;
	}

	/**
	 * Gets configuration of vendor checkboxes.
	 *
	 * @param string     $carrierId      Carrier id.
	 * @param array|null $carrierOptions Carrier options.
	 *
	 * @return array
	 */
	private function getVendorCheckboxesConfig( string $carrierId, ?array $carrierOptions ): array {
		$availableVendors = $this->getAvailableVendors( $carrierId );
		if ( null === $availableVendors || $this->isAvailableVendorsCountLowerThanRequiredMinimum( $availableVendors ) ) {
			return [];
		}

		$vendorCheckboxes = [];
		$vendorCarriers   = $this->pickupPointsConfig->getVendorCarriers();
		foreach ( $availableVendors as $vendorId ) {
			$vendorProvider        = $vendorCarriers[ $vendorId ];
			$checkbox              = [
				'group'    => $vendorProvider->getGroup(),
				'name'     => $vendorProvider->getName(),
				'disabled' => null,
				'default'  => null,
			];
			$hasGroupSettingsSaved = isset( $carrierOptions['vendor_groups'] );
			$hasTheGroupAllowed    = (
				$hasGroupSettingsSaved &&
				in_array( $vendorProvider->getGroup(), $carrierOptions['vendor_groups'], true )
			);
			if ( ! $hasGroupSettingsSaved || $hasTheGroupAllowed ) {
				$checkbox['default'] = true;
			}
			$vendorCheckboxes[] = $checkbox;
		}

		return $vendorCheckboxes;
	}

	/**
	 * Check if the number of vendors is lower than the required minimum
	 *
	 * @param array $availableVendors Available vendors.
	 *
	 * @return bool
	 */
	public function isAvailableVendorsCountLowerThanRequiredMinimum( array $availableVendors ): bool {
		return count( $availableVendors ) <= self::MINIMUM_CHECKED_VENDORS;
	}

	/**
	 * Checks if the number of vendors is lower than the minimum required by the carrier id
	 *
	 * @param string $carrierId Carrier id.
	 *
	 * @return bool
	 */
	public function isAvailableVendorsCountLowByCarrierId( string $carrierId ): bool {
		$availableVendors = $this->getAvailableVendors( $carrierId );
		return is_array( $availableVendors ) && $this->isAvailableVendorsCountLowerThanRequiredMinimum( $availableVendors );
	}
}
