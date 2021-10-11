<?php
/**
 * Class Modal
 *
 * @package Packetery\Module\Order
 */

declare( strict_types=1 );


namespace Packetery\Module\Order;

use PacketeryLatte\Engine;
use PacketeryNette\Forms\Form;

/**
 * Class Modal
 *
 * @package Packetery\Module\Order
 */
class Modal {

	/**
	 * @var Engine
	 */
	private $latteEngine;

	/**
	 * @var Controller
	 */
	private $orderController;

	/**
	 * @var \Packetery\Module\FormFactory
	 */
	private $formFactory;

	/**
	 * Modal constructor.
	 *
	 * @param Engine                        $latteEngine     Latte engine.
	 * @param Controller                    $orderController Order controller.
	 * @param \Packetery\Module\FormFactory $formFactory
	 */
	public function __construct( Engine $latteEngine, Controller $orderController, \Packetery\Module\FormFactory $formFactory ) {
		$this->latteEngine     = $latteEngine;
		$this->orderController = $orderController;
		$this->formFactory = $formFactory;
	}

	/**
	 * Registers order modal.
	 */
	public function register(): void {
		add_action( 'admin_head', [ $this, 'renderTemplate' ] );
	}

	public function renderTemplate(): void {
		$nonce        = wp_create_nonce( 'wp_rest' );
		$orderSaveUrl = $this->orderController->getRoute( '/save' );
		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/order/modal-template.latte',
			[
				'nonce'        => $nonce,
				'orderSaveUrl' => $orderSaveUrl,
				'form'         => $this->createForm()
			]
		);
	}

	/**
	 * Creates order modal form.
	 *
	 * @return Form
	 */
	public function createForm(): Form {
		$form = $this->formFactory->create();
		$form->addText( 'packetery_weight', __( 'Weight', 'packetery' ) )
			 ->setRequired( false )
			 ->addRule( Form::FLOAT );

		$form->addSubmit( 'submit',  __( 'Save', 'packetery' ) );

		$form->setDefaults( [
			'packetery_weight' => '{{ data.order.packetery_weight }}'
		] );

		return $form;
	}
}
