<?php
/**
 * Class StoredUntilModal.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Latte\Engine;
use Packetery\Module\Api\Internal\OrderRouter;
use Packetery\Module\Forms\StoredUntilFormFactory;
use Packetery\Nette\Forms\Controls\SubmitButton;

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
	 * Order router.
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
		add_action( 'admin_head', [ $this, 'renderModal' ] );
	}

	/**
	 * Renders packet modal.
	 *
	 * @return void
	 */
	public function renderModal(): void {
		$form  = $this->storedUntilFormFactory->createForm( sprintf( '%s_form', self::MODAL_ID ) );
		$nonce = wp_create_nonce( 'wp_rest' );

		$storedUntilSaveUrl = $this->orderRouter->getSaveStoredUntilUrl();

		$submitButton = $form['submit'];
		if ( $submitButton instanceof SubmitButton &&
			$submitButton->isSubmittedBy()
		) {
			$form->fireEvents();
		}

		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/order/stored-until-modal.latte',
			[
				'id'                 => self::MODAL_ID,
				'form'               => $form,
				'nonce'              => $nonce,
				'storedUntilSaveUrl' => $storedUntilSaveUrl,
				'translations'       => [
					'closeModalPanel' => __( 'Close modal panel', 'packeta' ),
					// translators: %s: Order number.
					'order#%s'        => __( 'Order #%s', 'packeta' ),
				],
			]
		);
	}
}
