<?php
/**
 * Class OptionsPage
 *
 * @package Packetery\Options
 */

declare( strict_types=1 );

namespace Packetery\Carrier;

use Nette\Forms\Form;
use Nette\Http\Request;
use Nette\Utils\ArrayHash;
use Packetery\FormFactory;

/**
 * Class OptionsPage
 *
 * @package Packetery\Options
 */
class OptionsPage {

	/**
	 * Latte_engine.
	 *
	 * @var \Latte\Engine Latte engine.
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
	 * Nette Request.
	 *
	 * @var Request Nette Request.
	 */
	private $httpRequest;

	/**
	 * Internal pickup points.
	 *
	 * @var string[] Internal pickup points.
	 */
	private $zpointCarriers;

	/**
	 * Plugin constructor.
	 *
	 * @param \Latte\Engine $latteEngine Latte_engine.
	 * @param Repository $carrierRepository Carrier repository.
	 * @param FormFactory $formFactory Form factory.
	 * @param Request $httpRequest Nette Request.
	 */
	public function __construct( \Latte\Engine $latteEngine, Repository $carrierRepository, FormFactory $formFactory, Request $httpRequest ) {
		$this->latteEngine       = $latteEngine;
		$this->carrierRepository = $carrierRepository;
		$this->formFactory       = $formFactory;
		$this->httpRequest       = $httpRequest;
		$this->zpointCarriers    = [
			'cz' => [
				'id'   => 'zpointcz',
				'name' => __( 'CZ Packeta pickup points', 'packetery' ),
			],
			'sk' => [
				'id'   => 'zpointsk',
				'name' => __( 'SK Packeta pickup points', 'packetery' ),
			],
			'hu' => [
				'id'   => 'zpointhu',
				'name' => __( 'HU Packeta pickup points', 'packetery' ),
			],
			'ro' => [
				'id'   => 'zpointro',
				'name' => __( 'RO Packeta pickup points', 'packetery' ),
			],
		];
	}

	/**
	 * Registers WP callbacks.
	 */
	public function register(): void {
		add_submenu_page(
			'packeta-options',
			__( 'Carrier settings', 'packetery' ),
			__( 'Carrier settings', 'packetery' ),
			'manage_options',
			'packeta-country',
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
	 * @return \Nette\Forms\Form
	 */
	private function createForm( array $carrierData ): \Nette\Forms\Form {
		$optionId = 'packetery_carrier_' . $carrierData['id'];

		$form = $this->formFactory->create( $optionId );
		$form->setAction( $this->httpRequest->getUrl() );

		$container = $form->addContainer( $optionId );

		$container->addCheckbox(
			'active',
			__( 'Active carrier', 'packetery' )
		);

		$container->addText( 'name', __( 'Display name', 'packetery' ) )
		          ->setRequired();

		$weightLimits = $container->addContainer( 'weight_limits' );
		if ( empty( $carrierData['weight_limits'] ) ) {
			$this->addWeightLimit( $weightLimits, 0 );
		} else {
			foreach ( $carrierData['weight_limits'] as $index => $limit ) {
				$this->addWeightLimit( $weightLimits, $index );
			}
		}

		$surchargeLimits = $container->addContainer( 'surcharge_limits' );
		if ( empty( $carrierData['surcharge_limits'] ) ) {
			$this->addSurchargeLimit( $surchargeLimits, 0 );
		} else {
			foreach ( $carrierData['surcharge_limits'] as $index => $limit ) {
				$this->addSurchargeLimit( $surchargeLimits, $index );
			}
		}

		$item = $container->addText( 'free_shipping_limit', __( 'Free shipping limit', 'packetery' ) );
		$item->addRule( $form::FLOAT, __( 'Please enter a valid decimal number.', 'packetery' ) );
		$container->addHidden( 'id' )->setRequired();

		$form->onValidate[] = [ $this, 'validateOptions' ];
		$form->onSuccess[] = [ $this, 'updateOptions' ];

		$carrierOptions       = get_option( $optionId );
		$carrierOptions['id'] = $carrierData['id'];
		if ( empty( $carrierOptions['name'] ) ) {
			$carrierOptions['name'] = $carrierData['name'];
		}
		$container->setDefaults( $carrierOptions );

		return $form;
	}

	/**
	 * Validates options.
	 *
	 * @param Form $form
	 */
	public function validateOptions( Form $form ): void {
		// todo sniffer
		list( $optionId, $options ) = $this->getOptionsFromData( $form );

		$options = $this->validateAndMergeNewLimits( $form, $options, 'weight_limits' );
		$options = $this->checkOverlappingAndSort(
			$form,
			$options,
			'weight_limits',
			'weight',
			__( 'Weight rules are overlapping, fix it please.', 'packetery' )
		);
		$options = $this->validateAndMergeNewLimits( $form, $options, 'surcharge_limits' );
		$options = $this->checkOverlappingAndSort(
			$form,
			$options,
			'surcharge_limits',
			'order_price',
			__( 'Surcharge rules are overlapping, fix it please.', 'packetery' )
		);

		// todo fix: hodnoty s new klici se neulozi
		$form[ $optionId ]->setValues( $options );
	}

	/**
	 * @param Form $form
	 *
	 * @return void
	 */
	public function updateOptions( Form $form ): void {
		list($optionId, $options) = $this->getOptionsFromData($form);
		update_option( $optionId, $options );
		if ( wp_safe_redirect( $this->httpRequest->getUrl(), 303 ) ) {
			exit;
		}
	}

	/**
	 * @param Form $form
	 *
	 * @return array
	 */
	private function getOptionsFromData( Form $form ): array {
		$formData = $form->getValues( 'array' );
		$optionId = array_keys( $formData )[0];

		return [ $optionId, (array) $formData[ $optionId ] ];
	}

	/**
	 * Save data if validated.
	 *
	 * @param array $forms Forms for all carriers.
	 */
	public function processForm(array $forms): void {
		/** @var Form $form */
		foreach ( $forms as $form ) {
			if ( $form->isSubmitted() ) {
				break;
			}
		}
		if ( $form ) {
			$form->fireEvents();
		}
	}

	/**
	 *  Renders page.
	 */
	public function render(): void {
		$countryIso = $this->httpRequest->getQuery( 'code' );
		if ( $countryIso ) {

			$countryCarriers = $this->carrierRepository->getByCountry( $countryIso );
			// Add PP carriers for 'cz', 'sk', 'hu', 'ro'.
			if ( ! empty( $this->zpointCarriers[ $countryIso ] ) ) {
				array_unshift( $countryCarriers, $this->zpointCarriers[ $countryIso ] );
			}

			$carriersData = array();
			foreach ( $countryCarriers as $carrierData ) {
				$optionId = 'packetery_carrier_' . $carrierData['id'];
				$options  = get_option( $optionId );
				if ( false !== $options ) {
					$carrierData += $options;
				}
				$carriersData[] = array(
					'form' => $this->createForm( $carrierData ),
					'data' => $carrierData,
				);
			}

			$this->processForm(array_column($carriersData, 'form'));

			$this->latteEngine->render(
				PACKETERY_PLUGIN_DIR . '/template/options/country.latte',
				array(
					'forms'       => $carriersData,
					'country_iso' => $countryIso,
				)
			);
		} else {
			// TODO: countries overview - fix in PES-263, CountryListingPage class.
		}
	}

	/**
	 * Transforms new_ keys to common numeric.
	 *
	 * @param array $options Options to merge.
	 * @param string $limitsContainer Container id.
	 *
	 * @return array
	 */
	private function validateAndMergeNewLimits(Form $form, array $options, string $limitsContainer ): array {
		$newOptions = array();
		if ( isset( $options[ $limitsContainer ] ) ) {
			foreach ( $options[ $limitsContainer ] as $key => $option ) {
				$keys = array_keys( $option );

				if ( $option[ $keys[0] ] && $option[ $keys[1] ] ) {
					if ( is_int( $key ) ) {
						$newOptions[ $key ] = $option;
					}
					if ( 0 === strpos( (string) $key, 'new_' ) ) {
						$newOptions[] = $option;
					}
				} elseif (
					( ! empty( $option[ $keys[0] ] ) && empty( $option[ $keys[1] ] ) ) ||
					( empty( $option[ $keys[0] ] ) && ! empty( $option[ $keys[1] ] ) )
				) {
					// TODO: JS validation.
					// todo use {inputError
					$errorMessage = __( 'Please fill in both values for each rule.', 'packetery' );
					add_settings_error( $limitsContainer, $limitsContainer, esc_attr( $errorMessage ) );
					$form->addError( $errorMessage );
				}
			}
			$options[ $limitsContainer ] = $newOptions;
		}

		return $options;
	}

	/**
	 * Checks rules overlapping and sorts rules.
	 *
	 * @param array $options Form data.
	 * @param string $limitsContainer Container id.
	 * @param string $limitKey Rule id.
	 * @param string $overlappingMessage Error message.
	 *
	 * @return array
	 */
	private function checkOverlappingAndSort(Form $form, array $options, string $limitsContainer, string $limitKey, string $overlappingMessage ): array {
		$limits = array_column( $options[ $limitsContainer ], $limitKey );
		if ( count( array_unique( $limits, SORT_NUMERIC ) ) !== count( $limits ) ) {
			// todo use {inputError
			add_settings_error( $limitsContainer, $limitsContainer, esc_attr( $overlappingMessage ) );
			$form->addError($overlappingMessage);
		}
		array_multisort( $limits, SORT_ASC, $options[ $limitsContainer ] );

		return $options;
	}

	/**
	 * Adds limit fields to form.
	 *
	 * @param \Nette\Forms\Container $weightLimits Container.
	 * @param int $index Index.
	 *
	 * @return void
	 */
	private function addWeightLimit( \Nette\Forms\Container $weightLimits, int $index ): void {
		$limit = $weightLimits->addContainer( (string) $index );
		$item  = $limit->addText( 'weight', __( 'Weight up to (kg)', 'packetery' ) );
		$item->setRequired();
		$item->addRule( Form::FLOAT, __( 'Please enter a valid decimal number.', 'packetery' ) );
		$item = $limit->addText( 'price', __( 'Price', 'packetery' ) );
		$item->setRequired();
		$item->addRule( Form::FLOAT, __( 'Please enter a valid decimal number.', 'packetery' ) );
	}

	/**
	 * Adds limit fields to form.
	 *
	 * @param \Nette\Forms\Container $surchargeLimits Container.
	 * @param int $index Index.
	 *
	 * @return void
	 */
	private function addSurchargeLimit( \Nette\Forms\Container $surchargeLimits, int $index ): void {
		$limit = $surchargeLimits->addContainer( (string) $index );
		$item  = $limit->addText( 'order_price', __( 'Order price up to', 'packetery' ) );
		$item->addRule( Form::FLOAT, __( 'Please enter a valid decimal number.', 'packetery' ) );
		$item = $limit->addText( 'surcharge', __( 'Surcharge', 'packetery' ) );
		$item->addRule( Form::FLOAT, __( 'Please enter a valid decimal number.', 'packetery' ) );
	}

}
