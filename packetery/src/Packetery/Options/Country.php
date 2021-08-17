<?php
/**
 * Class Country
 *
 * @package Packetery\Options
 */

declare( strict_types=1 );

namespace Packetery\Options;

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
	private $latte_engine;

	/**
	 * Carrier repository.
	 *
	 * @var Repository Carrier repository.
	 */
	private $carrier_repository;

	/**
	 * Form factory.
	 *
	 * @var FormFactory Form factory.
	 */
	private $formFactory;

	/**
	 * Internal pickup points.
	 *
	 * @var string[] Internal pickup points.
	 */
	private $zpointCarriers;

	/**
	 * Plugin constructor.
	 *
	 * @param \Latte\Engine $latte_engine Latte_engine.
	 * @param Repository    $carrier_repository Carrier repository.
	 * @param FormFactory   $formFactory Form factory.
	 */
	public function __construct( \Latte\Engine $latte_engine, Repository $carrier_repository, FormFactory $formFactory ) {
		$this->latte_engine       = $latte_engine;
		$this->carrier_repository = $carrier_repository;
		$this->formFactory        = $formFactory;
		$this->zpointCarriers     = [
			'cz' => [
				'id'   => 'zpointcz',
				'name' => __( 'CZ Zásilkovna výdejní místa', 'packetery' ),
			],
			'sk' => [
				'id'   => 'zpointsk',
				'name' => __( 'SK Zásilkovna výdejní místa', 'packetery' ),
			],
			'hu' => [
				'id'   => 'zpointhu',
				'name' => __( 'HU Zásilkovna výdejní místa', 'packetery' ),
			],
			'ro' => [
				'id'   => 'zpointro',
				'name' => __( 'RO Zásilkovna výdejní místa', 'packetery' ),
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
	 * @param array $carrier_data Country data.
	 *
	 * @return \Nette\Forms\Form
	 */
	private function create_form( array $carrier_data ): \Nette\Forms\Form {
		$optionId = 'packetery_carrier_' . $carrier_data['id'];
		$form     = $this->formFactory->create( $optionId );
		global $wp;
		$form->setAction( add_query_arg( $wp->query_vars, '' ) );

		$container = $form->addContainer( $optionId );

		$container->addCheckbox(
			'active',
			__( 'Active carrier', 'packetery' )
		);

		$container->addText( 'name', __( 'Display name', 'packetery' ) )
					->setRequired()
					->addRule( $form::MIN_LENGTH, __( 'Carrier display name must have at least 2 characters!', 'packetery' ), 2 );

		$weight_limits       = $container->addContainer( 'weight_limits' );
		$weight_limits_count = 0;
		if ( isset( $carrier_data['weight_limits'] ) ) {
			$weight_limits_count = count( $carrier_data['weight_limits'] );
		}
		for ( $i = 0; $i <= $weight_limits_count; $i ++ ) {
			$limit = $weight_limits->addContainer( (string) $i );
			$item  = $limit->addInteger( 'weight', __( 'Weight up to (kg)', 'packetery' ) );
			if ( 0 === $i ) {
				$item->setRequired();
			}
			$item = $limit->addInteger( 'price', __( 'Price', 'packetery' ) );
			if ( 0 === $i ) {
				$item->setRequired();
			}
		}

		$surcharge_limits       = $container->addContainer( 'surcharge_limits' );
		$surcharge_limits_count = 0;
		if ( isset( $carrier_data['surcharge_limits'] ) ) {
			$surcharge_limits_count = count( $carrier_data['surcharge_limits'] );
		}
		for ( $i = 0; $i <= $surcharge_limits_count; $i ++ ) {
			$limit = $surcharge_limits->addContainer( (string) $i );
			$limit->addInteger( 'order_price', __( 'Order price up to', 'packetery' ) );
			$limit->addInteger( 'surcharge', __( 'Surcharge', 'packetery' ) );
		}

		$container->addInteger( 'free_shipping_limit', __( 'Free shipping limit', 'packetery' ) );
		$container->addHidden( 'id' );

		$carrierOptions       = get_option( $optionId );
		$carrierOptions['id'] = $carrier_data['id'];
		if ( empty( $carrierOptions['name'] ) ) {
			$carrierOptions['name'] = $carrier_data['name'];
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
	public function options_validate( array $options ): array {
		if ( ! empty( $options['id'] ) ) {
			$options = $this->mergeNewOptions( $options, 'weight_limits' );
			$options = $this->checkOverlappingAndSort(
				$options,
				'weight_limits',
				'weight',
				__( 'Weight rules are overlapping, fix it please.', 'packetery' )
			);
			$options = $this->mergeNewOptions( $options, 'surcharge_limits' );
			$options = $this->checkOverlappingAndSort(
				$options,
				'surcharge_limits',
				'order_price',
				__( 'Surcharge rules are overlapping, fix it please.', 'packetery' )
			);

			$form     = $this->create_form( $options );
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
	public function process_form(): void {
		$factory = new \Nette\Http\RequestFactory();
		$request = $factory->fromGlobals();

		$post = $request->getPost();
		if ( $post ) {
			$options = $this->options_validate( $post[ $post['option_page'] ] );
			if ( ! get_settings_errors() ) {
				$optionId = 'packetery_carrier_' . $options['id'];
				update_option( $optionId, $options );
			}
		}
	}

	/**
	 *  Renders page.
	 */
	public function render(): void {
		// TODO: Processing form data without nonce verification - fix in PES-263.
		/*
		if (
			! isset( $_GET['_wpnonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'packetery_country' )
		) {
			wp_nonce_ays( '' );

			return;
		}
		*/

		if ( isset( $_GET['code'] ) ) {
			$this->process_form();

			$country_iso      = sanitize_text_field( wp_unslash( $_GET['code'] ) );
			$country_carriers = $this->carrier_repository->get_by_country( $country_iso );
			// Add PP for 'cz', 'sk', 'hu', 'ro'.
			if ( ! empty( $this->zpointCarriers[ $country_iso ] ) ) {
				array_unshift( $country_carriers, $this->zpointCarriers[ $country_iso ] );
			}

			$carriers_data = array();
			foreach ( $country_carriers as $carrier_data ) {
				$optionId = 'packetery_carrier_' . $carrier_data['id'];
				$options  = get_option( $optionId );
				if ( false !== $options ) {
					$carrier_data += $options;
				}
				$carriers_data[] = array(
					'form' => $this->create_form( $carrier_data ),
					'data' => $carrier_data,
				);
			}
			$this->latte_engine->render(
				PACKETERY_PLUGIN_DIR . '/template/options/country.latte',
				array(
					'forms'       => $carriers_data,
					'country_iso' => $country_iso,
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
	 * @param string $options_key Container id.
	 *
	 * @return array
	 */
	private function mergeNewOptions( array $options, string $options_key ): array {
		$new_options = array();
		if ( isset( $options[ $options_key ] ) ) {
			foreach ( $options[ $options_key ] as $key => $option ) {
				$keys = array_keys( $option );
				if ( $option[ $keys[0] ] && $option[ $keys[1] ] ) {
					if ( is_int( $key ) ) {
						$new_options[ $key ] = $option;
					}
					if ( 0 === strpos( (string) $key, 'new_' ) ) {
						$new_options[] = $option;
					}
				}
			}
			$options[ $options_key ] = $new_options;
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
		$limitsWeight = array_column( $options[ $limitsContainer ], $limitKey );
		if ( count( array_unique( $limitsWeight, SORT_NUMERIC ) ) !== count( $limitsWeight ) ) {
			add_settings_error( $limitsContainer, $limitsContainer, esc_attr( $overlappingMessage ) );
		}
		array_multisort( $limitsWeight, SORT_ASC, $options[ $limitsContainer ] );

		return $options;
	}

}
