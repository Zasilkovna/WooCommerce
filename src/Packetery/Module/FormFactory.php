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
				Validator::$messages[ Form::MIN ] = __( 'Please enter value greater than or equal to %d', PACKETERY_TEXT_DOMAIN );
				// translators: keep %d placeholder intact.
				Validator::$messages[ Form::MAX ]     = __( 'Please enter value less than or equal to %d', PACKETERY_TEXT_DOMAIN );
				Validator::$messages[ Form::INTEGER ] = __( 'Enter valid number', PACKETERY_TEXT_DOMAIN );
				Validator::$messages[ Form::FLOAT ]   = __( 'Enter valid number', PACKETERY_TEXT_DOMAIN );
				Validator::$messages[ Form::FILLED ]  = __( 'This field is required', PACKETERY_TEXT_DOMAIN );
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
}
