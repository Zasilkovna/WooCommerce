<?php
/**
 * Class Modal
 *
 * @package Packetery\Module\Order
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Helper;
use Packetery\Module\Api;
use Packetery\Module\FormFactory;
use Packetery\Module\FormValidators;
use Packetery\Latte\Engine;
use Packetery\Nette\Forms\Form;

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
	 * Order router.
	 *
	 * @var Api\Internal\OrderRouter;
	 */
	private $apiRouter;

	/**
	 * Modal constructor.
	 *
	 * @param Engine                   $latteEngine Latte engine.
	 * @param FormFactory              $formFactory Form factory.
	 * @param Api\Internal\OrderRouter $apiRouter   API router.
	 */
	public function __construct( Engine $latteEngine, FormFactory $formFactory, Api\Internal\OrderRouter $apiRouter ) {
		$this->latteEngine = $latteEngine;
		$this->formFactory = $formFactory;
		$this->apiRouter   = $apiRouter;
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
		$orderSaveUrl = $this->apiRouter->getSaveModalUrl();
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
		$form->addText( 'packetery_deliver_on', __( 'Planned dispatch', 'packeta' ) )
			->setHtmlAttribute( 'class', 'date-picker' )
			->setHtmlAttribute( 'autocomplete', 'off' )
			->setRequired( false )
			// translators: %s: Represents minimal date for delayed delivery.
			->addRule( [ FormValidators::class, 'dateIsLater' ], __( 'Date must be later than %s', 'packeta' ), wp_date( Helper::DATEPICKER_FORMAT ) );

		$form->addSubmit( 'submit', __( 'Save', 'packeta' ) );
		$form->addButton( 'cancel', __( 'Cancel', 'packeta' ) );

		$form->setDefaults(
			[
				'packetery_weight'          => '{{ data.order.packetery_weight }}',
				'packetery_original_weight' => '{{ data.order.packetery_original_weight }}',
				'packetery_length'          => '{{ data.order.packetery_length }}',
				'packetery_width'           => '{{ data.order.packetery_width }}',
				'packetery_height'          => '{{ data.order.packetery_height }}',
				'packetery_deliver_on'      => '{{ data.order.packetery_deliver_on }}',
			]
		);

		return $form;
	}
}
