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
	 * @var Request
	 */
	private $request;

	/**
	 * Plugin constructor.
	 *
	 * @param Request $request
	 */
	public function __construct( Request $request) {
		Validator::$messages[ Form::FILLED ] = __( 'This field is required!', 'packetery' );
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
		$form->httpRequest = $this->request;
		return $form;
	}
}
