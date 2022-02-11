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
use PacketeryNette\Http\Request;

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
				Validator::$messages[ Form::MIN ] = __( 'pleaseEnterValueGreaterThanOrEqualTo%d', 'packetery' );
				// translators: keep %d placeholder intact.
				Validator::$messages[ Form::MAX ]     = __( 'pleaseEnterValueLessThanOrEqualTo%d', 'packetery' );
				Validator::$messages[ Form::INTEGER ] = __( 'enterValidNumber', 'packetery' );
				Validator::$messages[ Form::FLOAT ]   = __( 'enterValidNumber', 'packetery' );
				Validator::$messages[ Form::FILLED ]  = __( 'thisFieldIsRequired', 'packetery' );
			}
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
}
