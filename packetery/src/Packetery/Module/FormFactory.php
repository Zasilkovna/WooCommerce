<?php
/**
 * Class FormFactory
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module;

use PacketeryNette\Forms\Form;
use PacketeryNette\Forms\Validator;

/**
 * Class FormFactory
 *
 * @package Packetery
 */
class FormFactory {

	/**
	 * Plugin constructor.
	 */
	public function __construct() {
		add_action(
			'init',
			function () {
				Validator::$messages[ Form::FILLED ] = __( 'thisFieldIsRequired', 'packetery' );
				Validator::$messages[ Form::FLOAT ]  = __( 'enterValidNumber', 'packetery' );
			}
		);
	}

	/**
	 * Creates Form
	 *
	 * @param string|null $name Form name.
	 *
	 * @return Form
	 */
	public function create( ?string $name = null ): Form {
		return new Form( $name );
	}
}
