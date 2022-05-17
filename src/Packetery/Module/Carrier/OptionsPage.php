<?php
/**
 * Class OptionsPage
 *
 * @package Packetery\Options
 */

declare( strict_types=1 );

namespace Packetery\Module\Carrier;

use Packetery\Core\Helper;
use Packetery\Module\Checkout;
use Packetery\Module\FormFactory;
use Packetery\Module\FormValidators;
use Packetery\Module\MessageManager;
use PacketeryLatte\Engine;
use PacketeryNette\Forms\Container;
use PacketeryNette\Forms\Form;
use PacketeryNette\Http\Request;

/**
 * Class OptionsPage
 *
 * @package Packetery\Options
 */
class OptionsPage {

	public const FORM_FIELD_NAME = 'name';
	public const SLUG            = 'packeta-country';

	/**
	 * PacketeryLatte_engine.
	 *
	 * @var Engine PacketeryLatte engine.
	 */
	private $latteEngine;

	/**
	 * Carrier repository.
	 *
	 * @var Repository Carrier repository.
	 */
	private $carrierRepository;

	/**
	 * Form factory.
	 *
	 * @var FormFactory Form factory.
	 */
	private $formFactory;

	/**
	 * PacketeryNette Request.
	 *
	 * @var Request PacketeryNette Request.
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
	 * Plugin constructor.
	 *
	 * @param Engine             $latteEngine PacketeryLatte_engine.
	 * @param Repository         $carrierRepository Carrier repository.
	 * @param FormFactory        $formFactory Form factory.
	 * @param Request            $httpRequest PacketeryNette Request.
	 * @param CountryListingPage $countryListingPage CountryListingPage.
	 * @param MessageManager     $messageManager Message manager.
	 */
	public function __construct(
		Engine $latteEngine,
		Repository $carrierRepository,
		FormFactory $formFactory,
		Request $httpRequest,
		CountryListingPage $countryListingPage,
		MessageManager $messageManager
	) {
		$this->latteEngine        = $latteEngine;
		$this->carrierRepository  = $carrierRepository;
		$this->formFactory        = $formFactory;
		$this->httpRequest        = $httpRequest;
		$this->countryListingPage = $countryListingPage;
		$this->messageManager     = $messageManager;
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
		$optionId = Checkout::CARRIER_PREFIX . $carrierData['id'];

		$form = $this->formFactory->create( $optionId );

		$form->addCheckbox(
			'active',
			__( 'Active carrier', 'packeta' ) . ':'
		);

		$form->addText( self::FORM_FIELD_NAME, __( 'Display name', 'packeta' ) . ':' )
			->setRequired();

		$weightLimits = $form->addContainer( 'weight_limits' );
		if ( empty( $carrierData['weight_limits'] ) ) {
			$this->addWeightLimit( $weightLimits, 0 );
		} else {
			foreach ( $carrierData['weight_limits'] as $index => $limit ) {
				$this->addWeightLimit( $weightLimits, $index );
			}
		}

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

		$item = $form->addText( 'free_shipping_limit', __( 'Free shipping limit', 'packeta' ) . ':' );
		$item->addRule( $form::FLOAT, __( 'Please enter a valid decimal number.', 'packeta' ) );
		$form->addHidden( 'id' )->setRequired();
		$form->addSubmit( 'save' );

		$carrier = $this->carrierRepository->getById( (int) $carrierData['id'] );
		if ( $carrier && false === $carrier->hasPickupPoints() ) {
			$addressValidationOptions = [
				'none'     => __( 'No address validation', 'packeta' ),
				'optional' => __( 'Optional address validation', 'packeta' ),
				'required' => __( 'Required address validation', 'packeta' ),
			];
			$form->addSelect( 'address_validation', __( 'Address validation', 'packeta' ) . ':', $addressValidationOptions )
				->setDefaultValue( 'none' );
		}

		$form->onValidate[] = [ $this, 'validateOptions' ];
		$form->onSuccess[]  = [ $this, 'updateOptions' ];

		$carrierOptions       = get_option( $optionId );
		$carrierOptions['id'] = $carrierData['id'];
		if ( empty( $carrierOptions[ self::FORM_FIELD_NAME ] ) ) {
			$carrierOptions[ self::FORM_FIELD_NAME ] = $carrierData['name'];
		}
		$form->setDefaults( $carrierOptions );

		return $form;
	}

	/**
	 * Creates settings form.
	 *
	 * @param array $carrierData Carrier data.
	 *
	 * @return Form
	 */
	private function createFormTemplate( array $carrierData ): Form {
		$optionId = Checkout::CARRIER_PREFIX . $carrierData['id'];

		$form = $this->formFactory->create( $optionId . '_template' );

		$weightLimitsTemplate = $form->addContainer( 'weight_limits' );
		$this->addWeightLimit( $weightLimitsTemplate, 0 );

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
			add_settings_error( '', '', esc_attr( __( 'Some carrier data are invalid', 'packeta' ) ) );
			return;
		}

		$options = $form->getValues( 'array' );

		$this->checkOverlapping(
			$form,
			$options,
			'weight_limits',
			'weight',
			__( 'Weight rules are overlapping, fix it please.', 'packeta' )
		);
		$this->checkOverlapping(
			$form,
			$options,
			'surcharge_limits',
			'order_price',
			__( 'Surcharge rules are overlapping, fix it please.', 'packeta' )
		);
	}

	/**
	 * Saves carrier options. onSuccess callback.
	 *
	 * @param Form $form Form.
	 *
	 * @return void
	 */
	public function updateOptions( Form $form ): void {
		$options = $form->getValues( 'array' );

		$options = $this->mergeNewLimits( $options, 'weight_limits' );
		$options = $this->sortLimits( $options, 'weight_limits', 'weight' );
		$options = $this->mergeNewLimits( $options, 'surcharge_limits' );
		$options = $this->sortLimits( $options, 'surcharge_limits', 'order_price' );

		update_option( Checkout::CARRIER_PREFIX . $options['id'], $options );
		$this->messageManager->flash_message( __( 'Settings saved', 'packeta' ), MessageManager::TYPE_SUCCESS, MessageManager::RENDERER_PACKETERY, 'carrier-country' );

		if ( wp_safe_redirect(
			add_query_arg(
				[
					'page' => self::SLUG,
					'code' => $this->httpRequest->getQuery( 'code' ),
				],
				get_admin_url( null, 'admin.php' )
			),
			303
		) ) {
			exit;
		}
	}

	/**
	 *  Renders page.
	 */
	public function render(): void {
		$countryIso = $this->httpRequest->getQuery( 'code' );
		if ( $countryIso ) {
			$countryCarriers = $this->carrierRepository->getByCountryIncludingZpoints( $countryIso );
			$carriersData    = [];
			$post            = $this->httpRequest->getPost();
			foreach ( $countryCarriers as $carrier ) {
				if ( ! empty( $post ) && $post['id'] === $carrier->getId() ) {
					$formTemplate = $this->createFormTemplate( $post );
					$form         = $this->createForm( $post );
					if ( $form->isSubmitted() ) {
						$form->fireEvents();
					}
				} else {
					$carrierData = $carrier->__toArray();
					$options     = get_option( Checkout::CARRIER_PREFIX . $carrier->getId() );
					if ( false !== $options ) {
						$carrierData += $options;
					}
					$formTemplate = $this->createFormTemplate( $carrierData );
					$form         = $this->createForm( $carrierData );
				}

				$carriersData[] = [
					'form'         => $form,
					'formTemplate' => $formTemplate,
					'carrier'      => $carrier,
				];
			}

			$this->latteEngine->render(
				PACKETERY_PLUGIN_DIR . '/template/carrier/country.latte',
				[
					'forms'          => $carriersData,
					'country_iso'    => $countryIso,
					'globalCurrency' => get_woocommerce_currency_symbol(),
					'flashMessages'  => $this->messageManager->renderToString( MessageManager::RENDERER_PACKETERY, 'carrier-country' ),
					'translations'   => [
						'cannotUseThisCarrierBecauseRequiresCustomsDeclaration' => __( 'Cannot use this carrier because requires customs declaration', 'packeta' ),
						'delete'                       => __( 'Delete', 'packeta' ),
						'weightRules'                  => __( 'Weight rules', 'packeta' ),
						'addWeightRule'                => __( 'Add weight rule', 'packeta' ),
						'codSurchargeRules'            => __( 'COD surcharge rules', 'packeta' ),
						'addCodSurchargeRule'          => __( 'Add COD surcharge rule', 'packeta' ),
						'afterExceedingThisAmountShippingIsFree' => __( 'After exceeding this amount, shipping is free.', 'packeta' ),
						'addressValidationDescription' => __( 'Customer address validation', 'packeta' ),
						'saveChanges'                  => __( 'Save changes', 'packeta' ),
						'packeta'                      => __( 'Packeta', 'packeta' ),
						'countryOptions'               => __( 'Country options', 'packeta' ),
						'noKnownCarrierForThisCountry' => __( 'No known carrier for this country.', 'packeta' ),
					],
				]
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
		$limit = $weightLimits->addContainer( (string) $index );
		$item  = $limit->addText( 'weight', __( 'Weight up to', 'packeta' ) . ':' );
		$item->setRequired();
		$item->addRule( Form::FLOAT, __( 'Please enter a valid decimal number.', 'packeta' ) );
		// translators: %d is numeric threshold.
		$item->addRule( [ FormValidators::class, 'greaterThan' ], __( 'Enter number greater than %d', 'packeta' ), 0.0 );

		$item->addFilter(
			function ( float $value ) {
				return Helper::simplifyWeight( $value );
			}
		);
		// translators: %d is numeric threshold.
		$item->addRule( [ FormValidators::class, 'greaterThan' ], __( 'Enter number greater than %d', 'packeta' ), 0.0 );

		$item = $limit->addText( 'price', __( 'Price', 'packeta' ) . ':' );
		$item->setRequired();
		$item->addRule( Form::FLOAT, __( 'Please enter a valid decimal number.', 'packeta' ) );
		$item->addRule( Form::MIN, null, 0 );
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

}
