<?php
/**
 * Class Country
 *
 * @package Packetery\Options
 */

declare( strict_types=1 );

namespace Packetery\Options;

use Nette\Http\Request;
use Packetery\Carrier\Repository;
use Packetery\FormFactory;

/**
 * Class Country
 *
 * @package Packetery\Options
 */
class Country {

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
	 * @param Repository    $carrierRepository Carrier repository.
	 * @param FormFactory   $formFactory Form factory.
	 * @param Request       $httpRequest Nette Request.
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
	 * @param array $carrierData Country data.
	 *
	 * @return \Nette\Forms\Form
	 */
	private function createForm( array $carrierData ): \Nette\Forms\Form {
		$optionId = 'packetery_carrier_' . $carrierData['id'];
		$form     = $this->formFactory->create( $optionId );
		$form->setAction( $this->httpRequest->getUrl() );

		$container = $form->addContainer( $optionId );

		$container->addCheckbox(
			'active',
			__( 'Active carrier', 'packetery' )
		);

		$container->addText( 'name', __( 'Display name', 'packetery' ) )
					->setRequired()
					->addRule( $form::MIN_LENGTH, __( 'Carrier display name must have at least 2 characters!', 'packetery' ), 2 );

		$weightLimits = $container->addContainer( 'weight_limits' );
		if ( empty( $carrierData['weight_limits'] ) ) {
			$this->addWeightLimit( $form, $weightLimits, 0 );
		} else {
			foreach ( $carrierData['weight_limits'] as $index => $limit ) {
				$this->addWeightLimit( $form, $weightLimits, $index );
			}
		}

		$surchargeLimits = $container->addContainer( 'surcharge_limits' );
		if ( empty( $carrierData['surcharge_limits'] ) ) {
			$this->addSurchargeLimit( $form, $surchargeLimits, 0 );
		} else {
			foreach ( $carrierData['surcharge_limits'] as $index => $limit ) {
				$this->addSurchargeLimit( $form, $surchargeLimits, $index );
			}
		}

		$item = $container->addText( 'free_shipping_limit', __( 'Free shipping limit', 'packetery' ) );
		$item->addRule( $form::FLOAT, __( 'Please enter a valid decimal number.', 'packetery' ) );
		$container->addHidden( 'id' );

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
	 * @param array $options Packetery_options.
	 *
	 * @return array
	 */
	public function validateOptions( array $options ): array {
		if ( ! empty( $options['id'] ) ) {
			$options = $this->validateAndMergeNewLimits( $options, 'weight_limits' );
			$options = $this->checkOverlappingAndSort(
				$options,
				'weight_limits',
				'weight',
				__( 'Weight rules are overlapping, fix it please.', 'packetery' )
			);
			$options = $this->validateAndMergeNewLimits( $options, 'surcharge_limits' );
			$options = $this->checkOverlappingAndSort(
				$options,
				'surcharge_limits',
				'order_price',
				__( 'Surcharge rules are overlapping, fix it please.', 'packetery' )
			);

			$form     = $this->createForm( $options );
			$optionId = 'packetery_carrier_' . $options['id'];
			$form[ $optionId ]->setValues( $options );
			if ( $form->isValid() === false ) {
				foreach ( $form[ $optionId ]->getControls() as $control ) {
					if ( $control->hasErrors() === false ) {
						continue;
					}
					add_settings_error( $control->getCaption(), esc_attr( $control->getName() ), $control->getError() );
					$options[ $control->getName() ] = '';
				}
			}
		}

		return $options;
	}

	/**
	 * Save data if validated.
	 */
	public function processForm(): void {
		$post = $this->httpRequest->getPost();
		if ( $post ) {
			$options = $this->validateOptions( $post[ $post['option_page'] ] );
			if ( ! get_settings_errors() ) {
				$optionId = 'packetery_carrier_' . $options['id'];
				update_option( $optionId, $options );
				if ( wp_safe_redirect( $this->httpRequest->getUrl(), 303 ) ) {
					exit;
				}
			}
		}
	}

	/**
	 *  Renders page.
	 */
	public function render(): void {
		$countryIso = $this->httpRequest->getQuery( 'code' );
		if ( $countryIso ) {
			$this->processForm();
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
			$this->latteEngine->render(
				PACKETERY_PLUGIN_DIR . '/template/options/country.latte',
				array(
					'forms'       => $carriersData,
					'country_iso' => $countryIso,
				)
			);
		} else {
			// TODO: countries overview - fix in PES-263.
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
	private function validateAndMergeNewLimits( array $options, string $limitsContainer ): array {
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
					add_settings_error( $limitsContainer, $limitsContainer, esc_attr__( 'Please fill in both values for each rule.', 'packetery' ) );
				}
			}
			$options[ $limitsContainer ] = $newOptions;
		}

		return $options;
	}

	/**
	 * Checks rules overlapping and sorts rules.
	 *
	 * @param array  $options Form data.
	 * @param string $limitsContainer Container id.
	 * @param string $limitKey Rule id.
	 * @param string $overlappingMessage Error message.
	 *
	 * @return array
	 */
	private function checkOverlappingAndSort( array $options, string $limitsContainer, string $limitKey, string $overlappingMessage ): array {
		$limits = array_column( $options[ $limitsContainer ], $limitKey );
		if ( count( array_unique( $limits, SORT_NUMERIC ) ) !== count( $limits ) ) {
			add_settings_error( $limitsContainer, $limitsContainer, esc_attr( $overlappingMessage ) );
		}
		array_multisort( $limits, SORT_ASC, $options[ $limitsContainer ] );

		return $options;
	}

	/**
	 * Adds limit fields to form.
	 *
	 * @param \Nette\Forms\Form      $form Form.
	 * @param \Nette\Forms\Container $weightLimits Container.
	 * @param int                    $index Index.
	 *
	 * @return void
	 */
	private function addWeightLimit( \Nette\Forms\Form $form, \Nette\Forms\Container $weightLimits, int $index ): void {
		$limit = $weightLimits->addContainer( (string) $index );
		$item  = $limit->addText( 'weight', __( 'Weight up to (kg)', 'packetery' ) );
		$item->setRequired();
		$item->addRule( $form::FLOAT, __( 'Please enter a valid decimal number.', 'packetery' ) );
		$item = $limit->addText( 'price', __( 'Price', 'packetery' ) );
		$item->setRequired();
		$item->addRule( $form::FLOAT, __( 'Please enter a valid decimal number.', 'packetery' ) );
	}

	/**
	 * Adds limit fields to form.
	 *
	 * @param \Nette\Forms\Form      $form Form.
	 * @param \Nette\Forms\Container $surchargeLimits Container.
	 * @param int                    $index Index.
	 *
	 * @return void
	 */
	private function addSurchargeLimit( \Nette\Forms\Form $form, \Nette\Forms\Container $surchargeLimits, int $index ): void {
		$limit = $surchargeLimits->addContainer( (string) $index );
		$item  = $limit->addText( 'order_price', __( 'Order price up to', 'packetery' ) );
		$item->addRule( $form::FLOAT, __( 'Please enter a valid decimal number.', 'packetery' ) );
		$item = $limit->addText( 'surcharge', __( 'Surcharge', 'packetery' ) );
		$item->addRule( $form::FLOAT, __( 'Please enter a valid decimal number.', 'packetery' ) );
	}

}
