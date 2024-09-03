<?php
/**
 * Class Modal
 *
 * @package Packetery\Module\Order
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Module\Api\Internal\OrderRouter;
use Packetery\Latte\Engine;

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
	 * Order details form.
	 *
	 * @var Form
	 */
	private $orderForm;

	/**
	 * Order router.
	 *
	 * @var OrderRouter;
	 */
	private $apiRouter;

	/**
	 * Modal constructor.
	 *
	 * @param Engine      $latteEngine Latte engine.
	 * @param Form        $orderForm Order form.
	 * @param OrderRouter $apiRouter API router.
	 */
	public function __construct( Engine $latteEngine, Form $orderForm, OrderRouter $apiRouter ) {
		$this->latteEngine = $latteEngine;
		$this->orderForm   = $orderForm;
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

		$form = $this->orderForm->create();
		$form->addSubmit( 'submit', __( 'Save', 'packeta' ) );
		$form->addButton( 'cancel', __( 'Cancel', 'packeta' ) );

		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/order/modal-template.latte',
			[
				'nonce'        => $nonce,
				'orderSaveUrl' => $orderSaveUrl,
				'form'         => $form,
				'translations' => [
					// translators: %s: Order number.
					'order#%s'        => __( 'Order #%s', 'packeta' ),

					'closeModalPanel' => __( 'Close modal panel', 'packeta' ),
					'weightIsManual'  => __( 'Weight is manually set. To calculate weight remove the field content and save.', 'packeta' ),
					'adultContent'    => __( 'Adult content', 'packeta' ),
				],
			]
		);
	}
}
