<?php
/**
 * Class FormFactory
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module;

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
	 * Creating a form
	 *
	 * @param array $carrierOptions Carrier options.
	 * @return Form
	 */
	public function createCarrierChange( array $carrierOptions ): Form {
		$form = $this->create();
		$form->addHidden( 'packetery_carrier_metabox_nonce' );
		$form->setDefaults( [ 'packetery_carrier_metabox_nonce' => wp_create_nonce() ] );
		$form->addSelect( 'carrierId', __( 'Carrier:', 'packeta' ), $carrierOptions )
			->setRequired()
			->setPrompt( 'Pick a carrier' );

		$form->addSubmit( 'submit', __( 'Save', 'packeta' ) );
		$form->addSubmit( 'cancel', __( 'Cancel', 'packeta' ) );

		return $form;
	}
}
