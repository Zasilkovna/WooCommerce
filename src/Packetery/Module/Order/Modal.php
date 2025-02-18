<?php

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Latte\Engine;
use Packetery\Module\Api\Internal\OrderRouter;
use Packetery\Module\Framework\WpAdapter;

class Modal {

	/**
	 * @var Engine
	 */
	private $latteEngine;

	/**
	 * @var Form
	 */
	private $orderForm;

	/**
	 * @var OrderRouter
	 */
	private $apiRouter;

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	public function __construct(
		Engine $latteEngine,
		Form $orderForm,
		OrderRouter $apiRouter,
		WpAdapter $wpAdapter
	) {
		$this->latteEngine = $latteEngine;
		$this->orderForm   = $orderForm;
		$this->apiRouter   = $apiRouter;
		$this->wpAdapter   = $wpAdapter;
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
		$form->addSubmit( 'submit', $this->wpAdapter->__( 'Save', 'packeta' ) );
		$form->addButton( 'cancel', $this->wpAdapter->__( 'Cancel', 'packeta' ) );

		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/order/modal-template.latte',
			[
				'nonce'        => $nonce,
				'orderSaveUrl' => $orderSaveUrl,
				'form'         => $form,
				'translations' => [
					// translators: %s: Order number.
					'order#%s'        => $this->wpAdapter->__( 'Order #%s', 'packeta' ),

					'closeModalPanel' => $this->wpAdapter->__( 'Close modal panel', 'packeta' ),
					'weightIsManual'  => $this->wpAdapter->__( 'Weight is manually set. To calculate weight remove the field content and save.', 'packeta' ),
					'codIsManual'     => $this->wpAdapter->__( 'COD value is manually set. To calculate the value remove field content and save.', 'packeta' ),
					'valueIsManual'   => $this->wpAdapter->__( 'Order value is manually set. To calculate the value remove field content and save.', 'packeta' ),
					'adultContent'    => $this->wpAdapter->__( 'Adult content', 'packeta' ),
				],
			]
		);
	}
}
