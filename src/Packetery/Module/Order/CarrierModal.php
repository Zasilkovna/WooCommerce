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
	 * Order repository.
	 *
	 * @var CarrierModalFormFactory
	 */
	private $carrierModalFormFactory;

	/**
	 * Constructor.
	 *
	 * @param Engine                  $latteEngine             Latte engine.
	 * @param DetailCommonLogic       $detailCommonLogic       Detail common logic.
	 * @param Module\ContextResolver  $contextResolver         Context resolver.
	 * @param CarrierModalFormFactory $carrierModalFormFactory Carrier Modal form factory.
	 */
	public function __construct(
		Engine $latteEngine,
		DetailCommonLogic $detailCommonLogic,
		Module\ContextResolver $contextResolver,
		CarrierModalFormFactory $carrierModalFormFactory
	) {
		$this->latteEngine             = $latteEngine;
		$this->contextResolver         = $contextResolver;
		$this->detailCommonLogic       = $detailCommonLogic;
		$this->carrierModalFormFactory = $carrierModalFormFactory;
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
	 * Renders modal.
	 *
	 * @return void
	 */
	public function renderCarrierModal(): void {
		if ( false === $this->contextResolver->isOrderDetailPage()
			&& false === $this->detailCommonLogic->isPacketeryOrder()
		) {
			return;
		}

		$form              = $this->carrierModalFormFactory->createCarrierChange();
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
					'header'          => __( 'Set carrier', 'packeta' ),
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
}
