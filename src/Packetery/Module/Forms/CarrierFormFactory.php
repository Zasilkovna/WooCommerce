<?php

declare( strict_types=1 );

namespace Packetery\Module\Forms;

use Packetery\Core\Entity\Carrier;
use Packetery\Core\Rounder;
use Packetery\Module\Carrier\CarrierOptionsFactory;
use Packetery\Module\Carrier\EntityRepository;
use Packetery\Module\Carrier\OptionPrefixer;
use Packetery\Module\Carrier\Options;
use Packetery\Module\Carrier\OptionsPage;
use Packetery\Module\Carrier\PacketaPickupPointsConfig;
use Packetery\Module\FormFactory;
use Packetery\Module\FormValidators;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\MessageManager;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\PaymentGatewayHelper;
use Packetery\Nette\Forms\Controls\SelectBox;
use Packetery\Nette\Forms\Form;
use Packetery\Nette\Http\Request;

class CarrierFormFactory {
	private WpAdapter $wpAdapter;
	private FormFactory $formFactory;
	private ShippingFormHelper $shippingFormHelper;
	private OptionsProvider $optionsProvider;
	private PacketaPickupPointsConfig $pickupPointsConfig;
	private EntityRepository $carrierRepository;
	private Request $httpRequest;
	private MessageManager $messageManager;
	private CarrierOptionsFactory $carrierOptionsFactory;

	public function __construct(
		WpAdapter $wpAdapter,
		FormFactory $formFactory,
		ShippingFormHelper $shippingFormHelper,
		OptionsProvider $optionsProvider,
		PacketaPickupPointsConfig $pickupPointsConfig,
		EntityRepository $carrierRepository,
		Request $httpRequest,
		MessageManager $messageManager,
		CarrierOptionsFactory $carrierOptionsFactory
	) {
		$this->wpAdapter             = $wpAdapter;
		$this->formFactory           = $formFactory;
		$this->shippingFormHelper    = $shippingFormHelper;
		$this->optionsProvider       = $optionsProvider;
		$this->pickupPointsConfig    = $pickupPointsConfig;
		$this->carrierRepository     = $carrierRepository;
		$this->httpRequest           = $httpRequest;
		$this->messageManager        = $messageManager;
		$this->carrierOptionsFactory = $carrierOptionsFactory;
	}

	public function createFormTemplate( string $carrierId ): Form {
		$optionId = OptionPrefixer::getOptionId( $carrierId );

		$form = $this->formFactory->create( $optionId . '_template' );

		$form->addSelect(
			OptionsPage::FORM_FIELD_PRICING_TYPE,
			$this->wpAdapter->__( 'Pricing type', 'packeta' ),
			[
				Options::PRICING_TYPE_BY_WEIGHT        => $this->wpAdapter->__( 'By weight', 'packeta' ),
				Options::PRICING_TYPE_BY_PRODUCT_VALUE => $this->wpAdapter->__( 'By product value', 'packeta' ),
			]
		)->setDefaultValue( Options::PRICING_TYPE_BY_WEIGHT );

		/** @var SelectBox $pricingTypeTemplate */
		$pricingTypeTemplate = $form[ OptionsPage::FORM_FIELD_PRICING_TYPE ];

		$weightLimitsTemplate = $form->addContainer( OptionsPage::FORM_FIELD_WEIGHT_LIMITS );
		$this->shippingFormHelper->addWeightLimit( $weightLimitsTemplate, 0, $pricingTypeTemplate );

		$valueLimitsTemplate = $form->addContainer( OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS );
		$this->shippingFormHelper->addProductValueLimit( $valueLimitsTemplate, 0, $pricingTypeTemplate );

		$surchargeLimitsTemplate = $form->addContainer( 'surcharge_limits' );
		$this->shippingFormHelper->addSurchargeLimit( $surchargeLimitsTemplate, 0 );

		return $form;
	}

	/**
	 * Creates settings form.
	 *
	 * @param array<string, mixed> $carrierData Carrier data.
	 *
	 * @return Form
	 */
	public function createForm( array $carrierData ): Form {
		$carrierId = (string) $carrierData['id'];
		$optionId  = OptionPrefixer::getOptionId( $carrierId );

		$form = $this->formFactory->create( $optionId );

		$form->addCheckbox(
			OptionsPage::FORM_FIELD_ACTIVE,
			$this->wpAdapter->__( 'Active carrier', 'packeta' ) . ':'
		);

		$form->addText( OptionsPage::FORM_FIELD_NAME, $this->wpAdapter->__( 'Display name', 'packeta' ) . ':' )
			->setRequired();

		/** @var array<string, mixed>|false $carrierOptions */
		$carrierOptions   = get_option( $optionId );
		$vendorCheckboxes = $this->getVendorCheckboxesConfig( $carrierId, ( $carrierOptions !== false ? $carrierOptions : null ) );
		if ( count( $vendorCheckboxes ) > 0 ) {
			$vendorsContainer = $form->addContainer( 'vendor_groups' );
			foreach ( $vendorCheckboxes as $checkboxConfig ) {
				$checkboxControl = $vendorsContainer->addCheckbox( (string) $checkboxConfig['group'], (string) $checkboxConfig['name'] );
				if ( $checkboxConfig['disabled'] === true ) {
					$checkboxControl->setDisabled()->setOmitted( false );
				}
				if ( $checkboxConfig['default'] === true ) {
					$checkboxControl->setDefaultValue( true );
				}
			}
		}

		$form->addSelect(
			OptionsPage::FORM_FIELD_PRICING_TYPE,
			$this->wpAdapter->__( 'Pricing type', 'packeta' ),
			[
				Options::PRICING_TYPE_BY_WEIGHT        => $this->wpAdapter->__( 'By weight', 'packeta' ),
				Options::PRICING_TYPE_BY_PRODUCT_VALUE => $this->wpAdapter->__( 'By product value', 'packeta' ),
			]
		)
			->setDefaultValue( Options::PRICING_TYPE_BY_WEIGHT )
			->addCondition( Form::EQUAL, Options::PRICING_TYPE_BY_WEIGHT )
			->toggle( $this->shippingFormHelper->createFieldContainerId( $form, OptionsPage::FORM_FIELD_WEIGHT_LIMITS ) )
			->endCondition()
			->addCondition( Form::EQUAL, Options::PRICING_TYPE_BY_PRODUCT_VALUE )
			->toggle( $this->shippingFormHelper->createFieldContainerId( $form, OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS ) );

		$shippingClasses = [];
		if ( $this->optionsProvider->isWcCarrierConfigEnabled() ) {
			$shippingClasses = $this->shippingFormHelper->getShippingClasses();
		}

		if ( $this->optionsProvider->isWcCarrierConfigEnabled() && count( $shippingClasses ) > 0 ) {
			$form->addSelect(
				OptionsPage::FORM_FIELD_CLASS_CALC_TYPE,
				$this->wpAdapter->__( 'Calculation type for shipping classes', 'packeta' ),
				[
					'per_class'                => $this->wpAdapter->__( 'Per class (sum costs for each shipping class)', 'packeta' ),
					'per_order_most_expensive' => $this->wpAdapter->__( 'Per order (use the most expensive class)', 'packeta' ),
				]
			)->setDefaultValue( 'per_class' );
		}

		$weightLimits = $form->addContainer( OptionsPage::FORM_FIELD_WEIGHT_LIMITS );
		/** @var SelectBox $globalPricingType */
		$globalPricingType = $form[ OptionsPage::FORM_FIELD_PRICING_TYPE ];
		if (
			! isset( $carrierData[ OptionsPage::FORM_FIELD_WEIGHT_LIMITS ] ) ||
			! is_array( $carrierData[ OptionsPage::FORM_FIELD_WEIGHT_LIMITS ] ) ||
			count( $carrierData[ OptionsPage::FORM_FIELD_WEIGHT_LIMITS ] ) === 0
		) {
			$this->shippingFormHelper->addWeightLimit( $weightLimits, 0, $globalPricingType );
		} else {
			foreach ( $carrierData[ OptionsPage::FORM_FIELD_WEIGHT_LIMITS ] as $index => $limit ) {
				$this->shippingFormHelper->addWeightLimit( $weightLimits, $index, $globalPricingType );
			}
		}

		if ( $this->optionsProvider->isWcCarrierConfigEnabled() ) {
			$maxCartValue = $form->addInteger(
				OptionsPage::FORM_FIELD_MAX_CART_VALUE,
				$this->wpAdapter->__( 'Max value of products in cart', 'packeta' ) . ':'
			);
			$maxCartValue
				->setRequired( false )
				->addRule( Form::MIN, $this->wpAdapter->__( 'Please enter a valid whole number.', 'packeta' ), 1 );
		}

		$productValueLimits = $form->addContainer( OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS );
		if (
			! isset( $carrierData[ OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS ] ) ||
			! is_array( $carrierData[ OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS ] ) ||
			count( $carrierData[ OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS ] ) === 0
		) {
			$this->shippingFormHelper->addProductValueLimit( $productValueLimits, 0, $globalPricingType );
		} else {
			foreach ( $carrierData[ OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS ] as $index => $limit ) {
				$this->shippingFormHelper->addProductValueLimit( $productValueLimits, $index, $globalPricingType );
			}
		}

		// We don't expect id to be empty in this situation. This would indicate a data save error.
		$carrier = $this->carrierRepository->getAnyById( $carrierId );

		if ( $carrier !== null && $carrier->supportsCod() ) {
			$form->addText( 'default_COD_surcharge', __( 'Default COD surcharge', 'packeta' ) . ':' )
				->setRequired( false )
				->addRule( Form::FLOAT )
				->addRule( Form::MIN, null, 0 );

			$surchargeLimits = $form->addContainer( 'surcharge_limits' );
			if (
				isset( $carrierData['surcharge_limits'] ) &&
				is_array( $carrierData['surcharge_limits'] ) &&
				count( $carrierData['surcharge_limits'] ) > 0
			) {
				foreach ( $carrierData['surcharge_limits'] as $index => $limit ) {
					$this->shippingFormHelper->addSurchargeLimit( $surchargeLimits, $index );
				}
			}
			$roundingOptions = [
				Rounder::DONT_ROUND => $this->wpAdapter->__( 'No rounding', 'packeta' ),
				Rounder::ROUND_DOWN => $this->wpAdapter->__( 'Always round down', 'packeta' ),
				Rounder::ROUND_UP   => $this->wpAdapter->__( 'Always round up', 'packeta' ),
			];
			$form->addSelect( 'cod_rounding', $this->wpAdapter->__( 'COD rounding', 'packeta' ) . ':', $roundingOptions )
				->setDefaultValue( Rounder::DONT_ROUND );
		}

		$item = $form->addText( 'free_shipping_limit', $this->wpAdapter->__( 'Free shipping limit', 'packeta' ) . ':' );
		$item->setRequired( false )
			->addRule( Form::FLOAT )
			->addRule( Form::MIN, null, 0 );

		if ( $carrier !== null && $carrier->isCarDelivery() ) {
			$daysUntilShipping = $form->addText( 'days_until_shipping', $this->wpAdapter->__( 'Number of days until shipping', 'packeta' ) . ':' );
			$daysUntilShipping->setRequired()
				->addRule( $form::INTEGER, $this->wpAdapter->__( 'Please, enter a full number.', 'packeta' ) )
				->addRule( $form::MIN, null, 0 );

			$shippingTimeCutOff = $form->addText( 'shipping_time_cut_off', $this->wpAdapter->__( 'Shipping time cut off', 'packeta' ) . ':' );
			$shippingTimeCutOff->setHtmlAttribute( 'class', 'date-picker' )
				->setHtmlType( 'time' )
				->setRequired( false )
				->setNullable()
				// translators: %s: Represents the time we stop taking more orders for next shipment.
				->addRule( [ FormValidators::class, 'hasClockTimeFormat' ], $this->wpAdapter->__( 'Time must be between %1$s and %2$s.', 'packeta' ), [ '00:00', '23:59' ] );
		}

		$couponFreeShipping = $form->addContainer( 'coupon_free_shipping' );
		$couponFreeShipping->addCheckbox( 'active', __( 'Apply free shipping coupon', 'packeta' ) );
		$couponFreeShipping->addCheckbox( 'allow_for_fees', __( 'Apply free shipping coupon for fees', 'packeta' ) )
			->addConditionOn( $form['coupon_free_shipping']['active'], Form::FILLED )
			->toggle( $this->createCouponFreeShippingForFeesContainerId( $form ) );

		$dimensionsRestrictions = $form->addContainer( 'dimensions_restrictions' );
		$dimensionsRestrictions->addCheckbox( 'active', $this->wpAdapter->__( 'Maximum package size', 'packeta' ) );
		if (
			$carrier !== null && (
				strpos( $carrier->getId(), Carrier::VENDOR_GROUP_ZBOX ) !== false ||
				strpos( $carrier->getId(), Carrier::VENDOR_GROUP_ZPOINT ) === 0 ||
				( $carrier->hasPickupPoints() && is_numeric( $carrier->getId() ) )
			)
		) {
			$dimensionsRestrictions->addText( 'length', $this->wpAdapter->__( 'Length (cm)', 'packeta' ) )
				->setNullable()
				->addConditionOn( $form['dimensions_restrictions']['active'], Form::FILLED )
				->toggle( $this->createDimensionRestrictionContainerId( $form ) )
				->setRequired()
				->addRule( Form::INTEGER, $this->wpAdapter->__( 'Provide a full number!', 'packeta' ) )
				->addRule( Form::MIN, 'Value must be greater than 0', 1 );
			$dimensionsRestrictions->addText( 'width', $this->wpAdapter->__( 'Width (cm)', 'packeta' ) )
				->setNullable()
				->addConditionOn( $form['dimensions_restrictions']['active'], Form::FILLED )
				->toggle( $this->createDimensionRestrictionContainerId( $form ) )
				->setRequired()
				->addRule( Form::INTEGER, $this->wpAdapter->__( 'Provide a full number!', 'packeta' ) )
				->addRule( Form::MIN, 'Value must be greater than 0', 1 );
			$dimensionsRestrictions->addText( 'height', $this->wpAdapter->__( 'Height (cm)', 'packeta' ) )
				->setNullable()
				->addConditionOn( $form['dimensions_restrictions']['active'], Form::FILLED )
				->toggle( $this->createDimensionRestrictionContainerId( $form ) )
				->setRequired()
				->addRule( Form::INTEGER, $this->wpAdapter->__( 'Provide a full number!', 'packeta' ) )
				->addRule( Form::MIN, 'Value must be greater than 0', 1 );
		} else {

			$maximumLength = $dimensionsRestrictions->addText( 'maximum_length', $this->wpAdapter->__( 'Maximum length (cm)', 'packeta' ) );
			$maximumLength->setNullable();
			$maximumLength->addConditionOn( $form['dimensions_restrictions']['active'], Form::FILLED )
				->toggle( $this->createDimensionRestrictionContainerId( $form ) )
				->addRule( Form::INTEGER, $this->wpAdapter->__( 'Provide a full number!', 'packeta' ) )
				->addRule( Form::MIN, $this->wpAdapter->__( 'Value must be greater than 0', 'packeta' ), 1 );

			$dimensionsSum = $dimensionsRestrictions->addText( 'dimensions_sum', $this->wpAdapter->__( 'Sum of dimensions (cm)', 'packeta' ) );
			$dimensionsSum->setNullable();
			$dimensionsSum->addConditionOn( $form['dimensions_restrictions']['active'], Form::FILLED )
				->toggle( $this->createDimensionRestrictionContainerId( $form ) )
				->addRule( Form::INTEGER, $this->wpAdapter->__( 'Provide a full number!', 'packeta' ) )
				->addRule( Form::MIN, $this->wpAdapter->__( 'Value must be greater than 0', 'packeta' ), 1 );

			$maximumLength->addConditionOn( $form['dimensions_restrictions']['active'], Form::FILLED )
				->addConditionOn( $dimensionsSum, Form::BLANK )
				->addRule( Form::FILLED, $this->wpAdapter->__( 'You have to fill in Maximum length or Sum of dimensions', 'packeta' ) );

			$dimensionsSum->addConditionOn( $form['dimensions_restrictions']['active'], Form::FILLED )
				->addConditionOn( $maximumLength, Form::BLANK )
				->addRule( Form::FILLED, $this->wpAdapter->__( 'You have to fill in Maximum length or Sum of dimensions', 'packeta' ) );
		}

		$form->addHidden( 'id' )->setRequired();
		$form->addSubmit( 'save' );

		if (
			$carrier !== null &&
			$carrier->isCarDelivery() === false &&
			$carrier->hasPickupPoints() === false &&
			in_array( $carrier->getCountry(), Carrier::ADDRESS_VALIDATION_COUNTRIES, true )
		) {
			$addressValidationOptions = [
				'none'     => $this->wpAdapter->__( 'No address validation', 'packeta' ),
				'optional' => $this->wpAdapter->__( 'Optional address validation', 'packeta' ),
				'required' => $this->wpAdapter->__( 'Required address validation', 'packeta' ),
			];
			$form->addSelect( 'address_validation', $this->wpAdapter->__( 'Address validation', 'packeta' ) . ':', $addressValidationOptions )
				->setDefaultValue( 'none' );
		}

		if ( $carrier !== null && $carrier->supportsAgeVerification() ) {
			$form->addText( 'age_verification_fee', $this->wpAdapter->__( 'Age verification fee', 'packeta' ) . ':' )
				->setRequired( false )
				->addRule( Form::FLOAT )
				->addRule( Form::MIN, null, 0 );
		}

		$form->addMultiSelect(
			'disallowed_checkout_payment_methods',
			$this->wpAdapter->__( 'Disallowed checkout payment methods', 'packeta' ),
			PaymentGatewayHelper::getAvailablePaymentGatewayChoices()
		)->checkDefaultValue( false );

		$form->onValidate[] = [ $this, 'validateOptions' ];
		$form->onSuccess[]  = [ $this, 'updateOptions' ];

		if ( $carrierOptions === false ) {
			$carrierOptions = [
				'id'                         => $carrierData['id'],
				OptionsPage::FORM_FIELD_NAME => $carrierData['name'],
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
	public function createCouponFreeShippingForFeesContainerId( Form $form ): string {
		return sprintf( '%s_apply_free_shipping_coupon_allow_for_fees', $form->getName() );
	}

	public function createDimensionRestrictionContainerId( Form $form ): string {
		return sprintf( '%s_dimension_restrictions', $form->getName() );
	}

	/**
	 * Check if the number of vendors is lower than the required minimum
	 *
	 * @param string[] $availableVendors Available vendors.
	 *
	 * @return bool
	 */
	public function isAvailableVendorsCountLowerThanRequiredMinimum( array $availableVendors ): bool {
		return count( $availableVendors ) <= OptionsPage::MINIMUM_CHECKED_VENDORS;
	}

	/**
	 * Gets available vendors for compound carrier.
	 *
	 * @param string $id Compound carrier id.
	 *
	 * @return string[]|null
	 */
	public function getAvailableVendors( string $id ): ?array {
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
	 * @param string                    $carrierId      Carrier id.
	 * @param array<string, mixed>|null $carrierOptions Carrier options.
	 *
	 * @return array<int, array<string, string|true|null>>
	 */
	private function getVendorCheckboxesConfig( string $carrierId, ?array $carrierOptions ): array {
		$availableVendors = $this->getAvailableVendors( $carrierId );
		if ( $availableVendors === null || $this->isAvailableVendorsCountLowerThanRequiredMinimum( $availableVendors ) ) {
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
				is_array( $carrierOptions['vendor_groups'] ) &&
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
	 * Validates options.
	 *
	 * @param Form $form Form.
	 */
	public function validateOptions( Form $form ): void {
		if ( $form->hasErrors() ) {
			add_settings_error( '', '', esc_attr( $this->wpAdapter->__( 'Some carrier data is invalid', 'packeta' ) ) );

			return;
		}

		/** @var array<string, mixed> $options */
		$options = $form->getValues( 'array' );

		$checkedVendors = $this->getCheckedVendors( $options );
		if (
			isset( $options['vendor_groups'] ) &&
			is_array( $options['vendor_groups'] ) &&
			count( $options['vendor_groups'] ) >= OptionsPage::MINIMUM_CHECKED_VENDORS &&
			count( $checkedVendors ) < OptionsPage::MINIMUM_CHECKED_VENDORS
		) {
			$vendorMessage = __( 'Check at least two types of pickup points or use a carrier which delivers to the desired pickup point type.', 'packeta' );
			add_settings_error( 'vendor_groups', 'vendor_groups', esc_attr( $vendorMessage ) );
			$form->addError( $vendorMessage );
		}

		if ( $options[ OptionsPage::FORM_FIELD_PRICING_TYPE ] === Options::PRICING_TYPE_BY_WEIGHT ) {
			$this->shippingFormHelper->checkOverlapping(
				$form,
				$options,
				OptionsPage::FORM_FIELD_WEIGHT_LIMITS,
				'weight',
				$this->wpAdapter->__( 'Weight rules are overlapping, please fix them.', 'packeta' )
			);
		}

		if ( $options[ OptionsPage::FORM_FIELD_PRICING_TYPE ] === Options::PRICING_TYPE_BY_PRODUCT_VALUE ) {
			$this->shippingFormHelper->checkOverlapping(
				$form,
				$options,
				OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS,
				'price',
				$this->wpAdapter->__( 'Product price rules are overlapping, please fix them.', 'packeta' )
			);
		}

		if ( isset( $options['surcharge_limits'] ) ) {
			$this->shippingFormHelper->checkOverlapping(
				$form,
				$options,
				'surcharge_limits',
				'order_price',
				$this->wpAdapter->__( 'Surcharge rules are overlapping, please fix them.', 'packeta' )
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
		/** @var array<string, mixed> $options */
		$options    = $form->getValues( 'array' );
		$newVendors = $this->getCheckedVendors( $options );
		if ( count( $newVendors ) > 0 ) {
			$options['vendor_groups'] = $newVendors;
		}

		$persistedOptions      = $this->carrierOptionsFactory->createByCarrierId( (string) $options['id'] );
		$persistedOptionsArray = $persistedOptions->toArray();
		if ( isset( $persistedOptionsArray[ OptionsPage::OPTIONS_SECTION_PER_CLASS ] ) ) {
			$options[ OptionsPage::OPTIONS_SECTION_PER_CLASS ] = $persistedOptionsArray[ OptionsPage::OPTIONS_SECTION_PER_CLASS ];
		}

		if ( $options[ OptionsPage::FORM_FIELD_PRICING_TYPE ] === Options::PRICING_TYPE_BY_WEIGHT ) {
			$options = $this->shippingFormHelper->mergeNewLimits( $options, OptionsPage::FORM_FIELD_WEIGHT_LIMITS );
			$options = $this->shippingFormHelper->sortLimits( $options, OptionsPage::FORM_FIELD_WEIGHT_LIMITS, 'weight' );

			$options[ OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS ] = $persistedOptionsArray[ OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS ] ?? [];
		}

		if ( $options[ OptionsPage::FORM_FIELD_PRICING_TYPE ] === Options::PRICING_TYPE_BY_PRODUCT_VALUE ) {
			$options = $this->shippingFormHelper->mergeNewLimits( $options, OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS );
			$options = $this->shippingFormHelper->sortLimits( $options, OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS, 'value' );

			$options[ OptionsPage::FORM_FIELD_WEIGHT_LIMITS ] = $persistedOptionsArray[ OptionsPage::FORM_FIELD_WEIGHT_LIMITS ] ?? [];
		}

		if ( isset( $options['surcharge_limits'] ) ) {
			$options = $this->shippingFormHelper->mergeNewLimits( $options, 'surcharge_limits' );
			$options = $this->shippingFormHelper->sortLimits( $options, 'surcharge_limits', 'order_price' );
		}

		update_option( OptionPrefixer::getOptionId( $options['id'] ), $options );
		$this->messageManager->flash_message( __( 'Settings saved', 'packeta' ), MessageManager::TYPE_SUCCESS, MessageManager::RENDERER_PACKETERY, 'carrier-country' );

		if ( wp_safe_redirect(
			$this->shippingFormHelper->createUrl(
				(string) $this->httpRequest->getQuery( OptionsPage::PARAMETER_COUNTRY_CODE ),
				(string) $this->httpRequest->getQuery( OptionsPage::PARAMETER_CARRIER_ID )
			),
			303
		) ) {
			exit;
		}
	}

	/**
	 * Gets checked vendors.
	 *
	 * @param array<string, mixed> $options Form options.
	 *
	 * @return string[]
	 */
	private function getCheckedVendors( array $options ): array {
		$vendorCodes = [];
		if ( isset( $options['vendor_groups'] ) && is_array( $options['vendor_groups'] ) ) {
			$vendorCodes = $options['vendor_groups'];
		}
		$newVendors = [];
		/** @var string $vendorId */
		foreach ( $vendorCodes as $vendorId => $isChecked ) {
			if ( (bool) $isChecked === false ) {
				continue;
			}
			$newVendors[] = $vendorId;
		}

		return $newVendors;
	}
}
