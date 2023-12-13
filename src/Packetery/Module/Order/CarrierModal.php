<?php
/**
 * Class CarrierModal.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Module;
use Packetery\Latte\Engine;
use Packetery\Nette\Forms;
use function Packetery\bdump;

/**
 * Class CarrierModal.
 *
 * @package Packetery
 */
class CarrierModal {

	public const MODAL_ID = 'wc-packetery-carrier-modal';

	/**
	 * Latte engine.
	 *
	 * @var Engine
	 */
	private $latteEngine;

	/**
	 * Context resolver.
	 *
	 * @var Module\ContextResolver
	 */
	private $contextResolver;

	/**
	 * Order detail common logic.
	 *
	 * @var DetailCommonLogic
	 */
	private $detailCommonLogic;

	/**
	 * Order form.
	 *
	 * @var Form
	 */
	private $orderForm;

	/**
	 * Constructor.
	 *
	 * @param Engine                 $latteEngine       Latte engine.
	 * @param Module\ContextResolver $contextResolver   Context resolver.
	 * @param DetailCommonLogic      $detailCommonLogic Detail common logic.
	 * @param Form                   $orderForm         Order form.
	 */
	public function __construct(
		Engine $latteEngine,
		Module\ContextResolver $contextResolver,
		DetailCommonLogic $detailCommonLogic,
		Form $orderForm
	) {
		$this->latteEngine       = $latteEngine;
		$this->contextResolver   = $contextResolver;
		$this->detailCommonLogic = $detailCommonLogic;
		$this->orderForm         = $orderForm;
	}

	/**
	 * Registers order modal.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_head', [ $this, 'renderCarrierModal' ] );
	}

	/**
	 * Renders change carrier modal.
	 *
	 * @return void
	 */
	public function renderCarrierModal(): void {
		if ( false === $this->contextResolver->isOrderDetailPage() ) {
			return;
		}

		$this->renderModal();
	}

	/**
	 * Renders modal.
	 *
	 * @return void
	 */
	private function renderModal(): void {
		$order = $this->detailCommonLogic->getOrder();

		if ( null === $order ) {
			return;
		}

		$orderShippingCountry = $order->getShippingCountry();

		if ( null === $orderShippingCountry ) {
			return;
		}

		$form              = $this->initializeForm( $order->getShippingCountry() );
		$form->onSuccess[] = [ $this, 'onFormSuccess' ];

		if ( $form['submit']->isSubmittedBy() ) {
			$form->fireEvents();
		}

		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/order/carrier-modal.latte',
			[
				'id'           => self::MODAL_ID,
				'form'         => $form,
				'translations' => [
					'header'          => __( 'Change carrier', 'packeta' ),
					'closeModalPanel' => __( 'Close modal panel', 'packeta' ),
				],
			]
		);
	}

	/**
	 * On form success.
	 *
	 * @param Forms\Form $form Form.
	 * @return void
	 */
	public function onFormSuccess( Forms\Form $form ): void {
		$values = $form->getValues();

		// TODO: Finish the logic to retain the data.
	}

	/**
	 * Initializes form to render or process.
	 *
	 * @param string $orderShippingCountry Country of destination.
	 * @return Forms\Form
	 */
	private function initializeForm( string $orderShippingCountry ): Forms\Form {
		return $this->orderForm->changeCarrier( $orderShippingCountry );
	}
}
