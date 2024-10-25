<?php
/**
 * Class StoredUntilModal.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Module\Api\Internal\OrderRouter;
use Packetery\Module\Forms\StoredUntilFormFactory;
use Packetery\Latte\Engine;

/**
 * Class StoredUntilModal.
 *
 * @package Packetery
 */
class StoredUntilModal {

	public const MODAL_ID = 'wc-packetery-stored-until-modal';

	/**
	 * Latte engine.
	 *
	 * @var Engine
	 */
	private $latteEngine;

	/**
	 * Stored until form factory.
	 *
	 * @var StoredUntilFormFactory
	 */
	private $storedUntilFormFactory;

	/**
	 *  Order router.
	 *
	 * @var OrderRouter
	 */
	private $orderRouter;

	/**
	 * Constructor.
	 *
	 * @param Engine                 $latteEngine Latte engine.
	 * @param StoredUntilFormFactory $storedUntilFormFactory Stored until form factory.
	 * @param OrderRouter            $orderRouter Order router.
	 */
	public function __construct(
		Engine $latteEngine,
		StoredUntilFormFactory $storedUntilFormFactory,
		OrderRouter $orderRouter
	) {
		$this->latteEngine            = $latteEngine;
		$this->storedUntilFormFactory = $storedUntilFormFactory;
		$this->orderRouter            = $orderRouter;
	}

	/**
	 * Registers order modal.
	 */
	public function register(): void {
		add_action( 'admin_head', [ $this, 'renderPacketModal' ] );
	}

	/**
	 * Renders packet modal.
	 */
	public function renderPacketModal(): void {
		$this->renderModal(
			self::MODAL_ID
		);
	}

	/**
	 * Renders packet modal.
	 *
	 * @param string $id       Modal ID.
	 *
	 * @return void
	 */
	private function renderModal( string $id ): void {
		$form  = $this->storedUntilFormFactory->createForm( sprintf( '%s_form', $id ) );
		$nonce = wp_create_nonce( 'wp_rest' );

		$storedUntilSaveUrl = $this->orderRouter->getSaveStoredUntilUrl();
		if ( $form['submit']->isSubmittedBy() ) {
			$form->fireEvents();
		}

		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/order/stored-until-modal.latte',
			[
				'id'                 => $id,
				'form'               => $form,
				'nonce'              => $nonce,
				'storedUntilSaveUrl' => $storedUntilSaveUrl,
				'translations'       => [
					'closeModalPanel' => __( 'Close modal panel', 'packeta' ),
					// translators: %s is packet ID.
					'order#%s'        => __( 'Order #%s', 'packeta' ),
				],
			]
		);
	}

}