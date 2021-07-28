<?php
/**
 * Class Page
 *
 * @package Packetery\Options
 */

declare( strict_types=1 );


namespace Packetery\Options;

/**
 * Class Page
 *
 * @package Packetery\Options
 */
class Page {

	/**
	 * Latte_engine.
	 *
	 * @var \Latte\Engine Latte engine.
	 */
	private $latte_engine;

	/**
	 * Plugin constructor.
	 *
	 * @param \Latte\Engine $latte_engine Latte_engine.
	 */
	public function __construct( \Latte\Engine $latte_engine ) {
		$this->latte_engine = $latte_engine;
	}

	/**
	 * Registers WP callbacks.
	 */
	public function register() {
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_menu_page(
			__( 'Settings', 'packetery' ),
			__( 'Packeta', 'packetery' ),
			'manage_options',
			'packeta-options',
			array(
				$this,
				'render',
			),
			'dashicons-schedule',
			55 // todo move item to last position in menu
		);
	}

	/**
	 * Creates settings form.
	 *
	 * @return \Nette\Forms\Form
	 */
	private function create_form() {
		// todo move to FormFactory. Create FormFactory when multiple forms exist.
		\Nette\Forms\Validator::$messages[\Nette\Forms\Form::FILLED] = __( 'This field is required!', 'packetery' );

		$form = new \Nette\Forms\Form();
		$form->setAction( 'options.php' );

		$container = $form->addContainer( 'packetery_options' );
		$container->addText( 'packetery_api_password', __( 'API password', 'packetery' ) )
		          ->setRequired()
		          ->addRule($form::PATTERN, __( 'API password must be 32 characters long and must contain valid characters!' ), '[a-z\d]{32}');
		$container->addText( 'packetery_sender', __( 'Sender', 'packetery' ) )
		          ->setRequired();
		$container->addSelect(
			'packetery_packeta_label_format',
			__( 'Packeta Label Format', 'packetery' ),
			array(
				'A6 on A4'       => __( '105x148 mm (A6) label on a page of size 210x297 mm (A4)', 'packetery' ),
				'A6 on A6'       => __( '105x148 mm (A6) label on a page of the same size', 'packetery' ),
				'A7 on A7'       => __( '105x74 mm (A7) label on a page of the same size', 'packetery' ),
				'A7 on A4'       => __( '105x74 mm (A7) label on a page of size 210x297 mm (A4)', 'packetery' ),
				'105x35mm on A4' => __( '105x35 mm label on a page of size 210x297 mm (A4)', 'packetery' ),
				'A8 on A8'       => __( '50x74 mm (A8) label on a page of the same size', 'packetery' ),
			)
		);
		$container->addSelect(
			'packetery_carrier_label_format',
			__( 'Carrier Label Format', 'packetery' ),
			array(
				'A6 on A4' => __( '105x148 mm (A6) label on a page of size 210x297 mm (A4)', 'packetery' ),
				'A6 on A6' => __( '105x148 mm (A6) label on a page of the same size (offset argument is ignored for this format)', 'packetery' ),
			)
		);

		$container->addCheckbox(
			'packetery_allow_label_emailing',
			__( 'Allow Label Emailing', 'packetery' )
		);

		$options = get_option( 'packetery_options' );
		$container->setDefaults($options);

		return $form;
	}

	/**
	 *  Admin_init callback.
	 */
	public function admin_init() {
		register_setting( 'packetery_options', 'packetery_options', array( $this, 'options_validate' ) );
		add_settings_section( 'packetery_main', __( 'Main Settings', 'packetery' ), '', 'packeta-options' );
	}

	/**
	 * Validates options.
	 *
	 * @param array $options Packetery_options.
	 *
	 * @return array
	 */
	public function options_validate( $options ) {
		$form = $this->create_form();
		$form['packetery_options']->setValues($options);
		if ($form->isValid() === false) {
			foreach ( $form['packetery_options']->getControls() as $control ) {
				if ($control->hasErrors() === false) {
					continue;
				}

				add_settings_error($control->getCaption(), esc_attr( $control->getName() ), $control->getError());
				$options[$control->getName()] = '';
			}
		}

		$packetery_api_password = $form['packetery_options']['packetery_api_password'];
		if ($packetery_api_password->hasErrors() === false) {
			$api_pass = $packetery_api_password->getValue();
			$options['packetery_api_key'] = substr( $api_pass, 0, 16 );
		} else {
			$options['packetery_api_key'] = '';
		}

		return $options;
	}

	/**
	 *  Renders page.
	 */
	public function render() {
		$this->latte_engine->render( PACKETERY_PLUGIN_DIR . '/template/options/page.latte', array( 'form' => $this->create_form() ) );
	}
}
