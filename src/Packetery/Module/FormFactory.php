<?php
/**
 * Class FormFactory
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module;

use Packetery\Module\Options\Provider;
use Packetery\Nette\Forms\Container;
use Packetery\Nette\Forms\Controls\TextInput;
use Packetery\Nette\Forms\Form;
use Packetery\Nette\Forms\Validator;
use Packetery\Nette\Http\Request;

/**
 * Class FormFactory
 *
 * @package Packetery
 */
class FormFactory {

	/**
	 * HTTP Request.
	 *
	 * @var Request
	 */
	private $request;

	/**
	 * Plugin constructor.
	 *
	 * @param Request $request HTTP request.
	 */
	public function __construct( Request $request ) {
		add_action(
			'init',
			function () {
				// translators: keep %d placeholder intact.
				Validator::$messages[ Form::MIN ] = __( 'Please enter value greater than or equal to %d', 'packeta' );
				// translators: keep %d placeholder intact.
				Validator::$messages[ Form::MAX ] = __( 'Please enter value less than or equal to %d', 'packeta' );

				Validator::$messages[ Form::INTEGER ] = __( 'Enter valid number', 'packeta' );
				Validator::$messages[ Form::FLOAT ]   = __( 'Enter valid number', 'packeta' );
				Validator::$messages[ Form::FILLED ]  = __( 'This field is required', 'packeta' );
				// translators: %d is number of characters.
				Validator::$messages[ Form::LENGTH ] = __( 'Please enter exactly %d characters', 'packeta' );
				// translators: %d is number of characters.
				Validator::$messages[ Form::MAX_LENGTH ] = __( 'Please enter max %d characters', 'packeta' );
			},
			11
		);
		$this->request = $request;
	}

	/**
	 * Creates Form
	 *
	 * @param string|null $name Form name.
	 *
	 * @return Form
	 */
	public function create( ?string $name = null ): Form {
		$form = new Form( $name );
		$form->setHttpRequest( $this->request );
		$form->allowCrossOrigin();

		return $form;
	}


	/**
	 * Dimension form control
	 *
	 * @param Container $container Container.
	 * @param string    $name Name.
	 * @param string    $label Label.
	 * @param string    $unit Unit of measurement.
	 *
	 * @return TextInput
	 */
	public function addDimension( Container $container, string $name, string $label, string $unit ): TextInput {
		$textInput = $container->addText( $name, sprintf( '%s (%s)', $label, $unit ) );

		$textInput
			->setHtmlType( 'number' )
			->setHtmlAttribute( 'step', 'any' )
			->addRule( Form::MIN, null, $this->setMinValue( $unit ) )
			->addRule( $this->setNumType( $unit ), __( 'Enter a numeric value in the correct format!', 'packeta' ) );

		return $textInput;
	}

	/**
	 * Sets field's number type
	 *
	 * @param string $unit Unit of measurement.
	 *
	 * @return string
	 */
	private function setNumType( string $unit ): string {
		if ( Provider::DIMENSIONS_UNIT_CM === $unit ) {
			return Form::FLOAT;
		}

		return Form::INTEGER;
	}

	/**
	 * Sets the minimum of allowed value.
	 *
	 * @param string $unit Unit of measurement.
	 *
	 * @return float
	 */
	private function setMinValue( string $unit ): float {
		if ( Provider::DIMENSIONS_UNIT_CM === $unit ) {
			return 0.1;
		}

		return 1;
	}
}
