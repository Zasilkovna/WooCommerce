<?php

declare( strict_types=1 );

namespace Packetery\Module\Forms;

use Packetery\Module\Carrier\CarrierOptionsFactory;
use Packetery\Module\Carrier\OptionPrefixer;
use Packetery\Module\Carrier\Options;
use Packetery\Module\Carrier\OptionsPage;
use Packetery\Module\Carrier\ShippingClassPage;
use Packetery\Module\FormFactory;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\MessageManager;
use Packetery\Nette\Forms\Controls\SelectBox;
use Packetery\Nette\Forms\Form;
use Packetery\Nette\Http\Request;

class ShippingClassFormFactory {

	public const FORM_FIELD_CARRIER_ID = 'carrier_id';
	public const FORM_FIELD_CLASS_SLUG = 'class_slug';

	private WpAdapter $wpAdapter;
	private FormFactory $formFactory;
	private ShippingFormHelper $shippingFormHelper;
	private CarrierOptionsFactory $carrierOptionsFactory;
	private MessageManager $messageManager;
	private Request $httpRequest;

	public function __construct(
		WpAdapter $wpAdapter,
		FormFactory $formFactory,
		ShippingFormHelper $shippingFormHelper,
		CarrierOptionsFactory $carrierOptionsFactory,
		MessageManager $messageManager,
		Request $httpRequest
	) {
		$this->wpAdapter             = $wpAdapter;
		$this->formFactory           = $formFactory;
		$this->shippingFormHelper    = $shippingFormHelper;
		$this->carrierOptionsFactory = $carrierOptionsFactory;
		$this->messageManager        = $messageManager;
		$this->httpRequest           = $httpRequest;
	}

	/**
	 * @param array<string, string|int> $shippingClass
	 */
	public function createFromClassAndCarrier( array $shippingClass, string $carrierId ): Form {
		$optionId = OptionPrefixer::getOptionId( $carrierId );
		$form     = $this->formFactory->create( $optionId . '_' . $shippingClass['slug'] );
		$form->setAction(
			$this->shippingFormHelper->createUrl(
				null,
				$carrierId,
				(string) $shippingClass['slug']
			)
		);

		$form->addSelect(
			OptionsPage::FORM_FIELD_PRICING_TYPE,
			sprintf( $this->wpAdapter->__( 'Pricing type for class: %s', 'packeta' ), $shippingClass['name'] ),
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

		/** @var SelectBox $classPricingType */
		$classPricingType = $form[ OptionsPage::FORM_FIELD_PRICING_TYPE ];

		$carrierOptions = $this->carrierOptionsFactory->createByCarrierId( $carrierId );
		$carrierOptions = $carrierOptions->toArray();
		$classConfig    = $carrierOptions[ OptionsPage::OPTIONS_SECTION_PER_CLASS ][ $shippingClass['slug'] ] ?? [];

		/** @var array<string, mixed> $post */
		$post = $this->httpRequest->getPost();
		if ( isset( $post[ self::FORM_FIELD_CLASS_SLUG ] ) && $post[ self::FORM_FIELD_CLASS_SLUG ] === $shippingClass['slug'] ) {
			$classConfig = array_replace( $classConfig, $post );
			if ( ! isset( $post['surcharge_limits'] ) ) {
				$classConfig['surcharge_limits'] = [];
			}
		}

		$weightLimits = $form->addContainer( OptionsPage::FORM_FIELD_WEIGHT_LIMITS );
		$limitsData   = $classConfig[ OptionsPage::FORM_FIELD_WEIGHT_LIMITS ] ?? [];
		if ( count( $limitsData ) === 0 ) {
			$this->shippingFormHelper->addWeightLimit( $weightLimits, 0, $classPricingType );
		} else {
			foreach ( $limitsData as $index => $value ) {
				$this->shippingFormHelper->addWeightLimit( $weightLimits, $index, $classPricingType );
			}
		}

		$productValueLimits     = $form->addContainer( OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS );
		$productValueLimitsData = $classConfig[ OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS ] ?? [];
		if ( count( $productValueLimitsData ) === 0 ) {
			$this->shippingFormHelper->addProductValueLimit( $productValueLimits, 0, $classPricingType );
		} else {
			foreach ( $productValueLimitsData as $index => $value ) {
				$this->shippingFormHelper->addProductValueLimit( $productValueLimits, $index, $classPricingType );
			}
		}

		$form->addText( 'default_COD_surcharge', $this->wpAdapter->__( 'Default COD surcharge', 'packeta' ) )
			->setRequired( false )
			->addRule( Form::FLOAT )
			->addRule( Form::MIN, null, 0 );

		$surchargeLimits     = $form->addContainer( 'surcharge_limits' );
		$surchargeLimitsData = $classConfig['surcharge_limits'] ?? [];
		if ( count( $surchargeLimitsData ) > 0 ) {
			foreach ( $surchargeLimitsData as $index => $value ) {
				$this->shippingFormHelper->addSurchargeLimit( $surchargeLimits, $index );
			}
		}

		$form->addText( 'free_shipping_limit', $this->wpAdapter->__( 'Free shipping limit', 'packeta' ) )
			->setRequired( false )
			->addRule( Form::FLOAT )
			->addRule( Form::MIN, null, 0 );

		$form->addText( 'age_verification_fee', $this->wpAdapter->__( 'Age verification fee', 'packeta' ) )
			->setRequired( false )
			->addRule( Form::FLOAT )
			->addRule( Form::MIN, null, 0 );

		$form->addHidden( self::FORM_FIELD_CARRIER_ID, $carrierId );
		$form->addHidden( self::FORM_FIELD_CLASS_SLUG, $shippingClass['slug'] );

		$form->addSubmit( 'save' );

		$form->setDefaults( $classConfig );

		$form->onValidate[] = [ $this, 'validateOptions' ];
		$form->onSuccess[]  = [ $this, 'updateOptions' ];

		return $form;
	}

	public function validateOptions( Form $form ): void {
		/** @var array<string, mixed> $options */
		$options          = $form->getValues( 'array' );
		$classPricingType = $options[ OptionsPage::FORM_FIELD_PRICING_TYPE ] ?? $options[ OptionsPage::FORM_FIELD_PRICING_TYPE ] ?? Options::PRICING_TYPE_BY_WEIGHT;
		if ( $classPricingType === Options::PRICING_TYPE_BY_WEIGHT ) {
			$this->shippingFormHelper->checkOverlapping(
				$form,
				[ OptionsPage::FORM_FIELD_WEIGHT_LIMITS => ( $options[ OptionsPage::FORM_FIELD_WEIGHT_LIMITS ] ?? [] ) ],
				OptionsPage::FORM_FIELD_WEIGHT_LIMITS,
				'weight',
				$this->wpAdapter->__( 'Weight rules are overlapping in a shipping class, please fix them.', 'packeta' )
			);
		}
		if ( $classPricingType === Options::PRICING_TYPE_BY_PRODUCT_VALUE ) {
			$this->shippingFormHelper->checkOverlapping(
				$form,
				[ OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS => ( $options[ OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS ] ?? [] ) ],
				OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS,
				'price',
				$this->wpAdapter->__( 'Product price rules are overlapping in a shipping class, please fix them.', 'packeta' )
			);
		}
		if ( isset( $options['surcharge_limits'] ) ) {
			$this->shippingFormHelper->checkOverlapping(
				$form,
				[ 'surcharge_limits' => $options['surcharge_limits'] ],
				'surcharge_limits',
				'order_price',
				$this->wpAdapter->__( 'Surcharge rules are overlapping in a shipping class, please fix them.', 'packeta' )
			);
		}
	}

	/**
	 * @param array<string, mixed> $options
	 */
	public function updateOptions( array $options ): void {
		$carrierOptionId  = OptionPrefixer::getOptionId( (string) $options[ self::FORM_FIELD_CARRIER_ID ] );
		$carrierOptions   = $this->carrierOptionsFactory->createByOptionId( $carrierOptionId )->toArray();
		$classPricingType = $options[ OptionsPage::FORM_FIELD_PRICING_TYPE ] ?? $carrierOptions[ OptionsPage::FORM_FIELD_PRICING_TYPE ] ?? Options::PRICING_TYPE_BY_WEIGHT;

		if ( $classPricingType === Options::PRICING_TYPE_BY_WEIGHT ) {
			$weightLimitOptions = [ OptionsPage::FORM_FIELD_WEIGHT_LIMITS => ( $options[ OptionsPage::FORM_FIELD_WEIGHT_LIMITS ] ?? [] ) ];
			$weightLimitOptions = $this->shippingFormHelper->mergeNewLimits( $weightLimitOptions, OptionsPage::FORM_FIELD_WEIGHT_LIMITS );
			$weightLimitOptions = $this->shippingFormHelper->sortLimits( $weightLimitOptions, OptionsPage::FORM_FIELD_WEIGHT_LIMITS, 'weight' );

			$options[ OptionsPage::FORM_FIELD_WEIGHT_LIMITS ]        = $weightLimitOptions[ OptionsPage::FORM_FIELD_WEIGHT_LIMITS ];
			$options[ OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS ] = $options[ OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS ] ?? [];
		}

		if ( $classPricingType === Options::PRICING_TYPE_BY_PRODUCT_VALUE ) {
			$valueLimitOptions = [ OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS => ( $options[ OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS ] ?? [] ) ];
			$valueLimitOptions = $this->shippingFormHelper->mergeNewLimits( $valueLimitOptions, OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS );
			$valueLimitOptions = $this->shippingFormHelper->sortLimits( $valueLimitOptions, OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS, 'value' );

			$options[ OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS ] = $valueLimitOptions[ OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS ];
			$options[ OptionsPage::FORM_FIELD_WEIGHT_LIMITS ]        = $options[ OptionsPage::FORM_FIELD_WEIGHT_LIMITS ] ?? [];
		}

		if ( isset( $options['surcharge_limits'] ) ) {
			$surchargeLimits = [ 'surcharge_limits' => $options['surcharge_limits'] ];
			$surchargeLimits = $this->shippingFormHelper->mergeNewLimits( $surchargeLimits, 'surcharge_limits' );
			$surchargeLimits = $this->shippingFormHelper->sortLimits( $surchargeLimits, 'surcharge_limits', 'order_price' );

			$options['surcharge_limits'] = $surchargeLimits['surcharge_limits'];
		}

		$classSlug = $options[ self::FORM_FIELD_CLASS_SLUG ];
		$carrierOptions[ OptionsPage::OPTIONS_SECTION_PER_CLASS ][ $classSlug ] = $options;
		update_option( $carrierOptionId, $carrierOptions );

		$this->messageManager->flash_message(
			$this->wpAdapter->__( 'Settings saved', 'packeta' ),
			MessageManager::TYPE_SUCCESS,
			MessageManager::RENDERER_PACKETERY,
			'carrier-country'
		);

		if ( wp_safe_redirect(
			$this->shippingFormHelper->createUrl(
				null,
				(string) $this->httpRequest->getQuery( OptionsPage::PARAMETER_CARRIER_ID ),
				(string) $this->httpRequest->getQuery( ShippingClassPage::PARAMETER_CLASS_ID )
			),
			303
		) ) {
			exit;
		}
	}
}
