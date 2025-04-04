<?php
/**
 * Class FormFactory
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module;

use Packetery\Module\Options\OptionsProvider;
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
	 * @var Request
	 */
	private $request;

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
		$form              = new Form( $name );
		$form->httpRequest = $this->request;
		$form->allowCrossOrigin();

		return $form;
	}

	public function addDimension( Container $container, string $name, string $label, string $unit ): TextInput {
		$numberInputType = $this->getNumType( $unit );

		switch ( $numberInputType ) {
			case Form::INTEGER:
				$numberInput = $container->addInteger( $name, sprintf( '%s (%s)', $label, $unit ) );

				break;
			case Form::FLOAT:
				$numberInput = $container->addText( $name, sprintf( '%s (%s)', $label, $unit ) )->addRule( Form::FLOAT );

				break;
			default:
				throw new \InvalidArgumentException( 'Unsupported number input type: ' . $numberInputType );
		}

		$numberInput
			->setHtmlType( 'number' )
			->setHtmlAttribute( 'step', 'any' )
			->addRule( Form::MIN, null, $this->setMinValue( $unit ) );

		return $numberInput;
	}

	private function getNumType( string $unit ): string {
		if ( $unit === OptionsProvider::DIMENSIONS_UNIT_CM ) {
			return Form::FLOAT;
		}

		return Form::INTEGER;
	}

	private function setMinValue( string $unit ): float {
		if ( $unit === OptionsProvider::DIMENSIONS_UNIT_CM ) {
			return 0.1;
		}

		return 1;
	}
}
