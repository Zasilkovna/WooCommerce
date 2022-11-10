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
	 * @param Engine           $latteEngine Latte engine.
	 * @param FormFactory      $formFactory Form factory.
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
					// translators: %s: Order number.
					'order#%s'        => __( 'Order #%s', 'packeta' ),
					'closeModalPanel' => __( 'Close modal panel', 'packeta' ),
					'weightIsManual'  => __( 'Weight is manually set. To calculate weight remove the field content and save.', 'packeta' ),
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
		$form->addText( 'packetery_weight', __( 'Weight', 'packeta' ) . ' (kg)' )
			->setRequired( false )
			->addRule( Form::FLOAT );
		$form->addHidden( 'packetery_original_weight' )
			->setRequired( false )
			->addRule( Form::FLOAT );
		$form->addText( 'packetery_width', __( 'Width (mm)', 'packeta' ) )
			->setRequired( false )
			->addRule( Form::FLOAT, __( 'Provide numeric value!', 'packeta' ) );
		$form->addText( 'packetery_length', __( 'Length (mm)', 'packeta' ) )
			->setRequired( false )
			->addRule( Form::FLOAT, __( 'Provide numeric value!', 'packeta' ) );
		$form->addText( 'packetery_height', __( 'Height (mm)', 'packeta' ) )
			->setRequired( false )
			->addRule( Form::FLOAT, __( 'Provide numeric value!', 'packeta' ) );

		$form->addSubmit( 'submit', __( 'Save', 'packeta' ) );
		$form->addButton( 'cancel', __( 'Cancel', 'packeta' ) );

		$form->setDefaults(
			[
				'packetery_weight'          => '{{ data.order.packetery_weight }}',
				'packetery_original_weight' => '{{ data.order.packetery_original_weight }}',
				'packetery_length'          => '{{ data.order.packetery_length }}',
				'packetery_width'           => '{{ data.order.packetery_width }}',
				'packetery_height'          => '{{ data.order.packetery_height }}',
			]
		);

		return $form;
	}
}
