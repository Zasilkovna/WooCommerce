<?php
/**
 * Class Modal
 *
 * @package Packetery\Module\Order
 */

declare( strict_types=1 );


namespace Packetery\Module\Order;

use Packetery\Module\FormFactory;
use PacketeryLatte\Engine;
use PacketeryNette\Forms\Form;

/**
 * Class Modal
 *
 * @package Packetery\Module\Order
 */
class Modal {

	/**
	 * Latte engine.
	 *
	 * @var Engine
	 */
	private $latteEngine;

	/**
	 * Form factory.
	 *
	 * @var FormFactory
	 */
	private $formFactory;

	/**
	 * Order controller.
	 *
	 * @var ControllerRouter
	 */
	private $orderControllerRouter;

	/**
	 * Modal constructor.
	 *
	 * @param Engine           $latteEngine     Latte engine.
	 * @param FormFactory      $formFactory     Form factory.
	 * @param ControllerRouter $orderController Order controller.
	 */
	public function __construct( Engine $latteEngine, FormFactory $formFactory, ControllerRouter $orderController ) {
		$this->latteEngine           = $latteEngine;
		$this->formFactory           = $formFactory;
		$this->orderControllerRouter = $orderController;
	}

	/**
	 * Registers order modal.
	 */
	public function register(): void {
		add_action( 'admin_head', [ $this, 'renderTemplate' ] );
	}

	/**
	 * Renders template.
	 */
	public function renderTemplate(): void {
		$nonce        = wp_create_nonce( 'wp_rest' );
		$orderSaveUrl = $this->orderControllerRouter->getRouteUrl( Controller::PATH_SAVE_MODAL );
		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/order/modal-template.latte',
			[
				'nonce'        => $nonce,
				'orderSaveUrl' => $orderSaveUrl,
				'form'         => $this->createForm(),
				'translations' => [
					'order#%s'        => __( 'Order #%s', PACKETERY_LANG_DOMAIN ),
					'closeModalPanel' => __( 'Close modal panel', PACKETERY_LANG_DOMAIN ),
				],
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
		$form->addText( 'packetery_weight', __( 'Weight', PACKETERY_LANG_DOMAIN ) . ' (kg)' )
			->setRequired( false )
			->addRule( Form::FLOAT );

		$form->addSubmit( 'submit', __( 'Save', PACKETERY_LANG_DOMAIN ) );
		$form->addButton( 'cancel', __( 'Cancel', PACKETERY_LANG_DOMAIN ) );

		$form->setDefaults(
			[
				'packetery_weight' => '{{ data.order.packetery_weight }}',
			]
		);

		return $form;
	}
}
