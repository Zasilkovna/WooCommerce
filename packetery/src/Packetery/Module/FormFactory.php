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
		// translators: keep %d placeholder intact.
		Validator::$messages[ Form::MIN ] = __( 'pleaseEnterValueGreaterThanOrEqualTo%d', 'packetery' );
		// translators: keep %d placeholder intact.
		Validator::$messages[ Form::MAX ]     = __( 'pleaseEnterValueLessThanOrEqualTo%d', 'packetery' );
		Validator::$messages[ Form::INTEGER ] = __( 'pleaseEnterValidNumber', 'packetery' );
		Validator::$messages[ Form::FLOAT ]   = __( 'pleaseEnterValidNumber', 'packetery' );
		Validator::$messages[ Form::FILLED ]  = __( 'This field is required!', 'packetery' );
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
