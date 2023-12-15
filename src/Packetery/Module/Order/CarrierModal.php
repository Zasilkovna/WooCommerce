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
	 * Carrier repository.
	 *
	 * @var Module\Carrier\EntityRepository
	 */
	private $carrierRepository;

	/**
	 * Form factory.
	 *
	 * @var Module\FormFactory
	 */
	private $formFactory;

	/**
	 * Order repository.
	 *
	 * @var Repository
	 */
	private $orderRepository;

	/**
	 * Constructor.
	 *
	 * @param Engine                          $latteEngine       Latte engine.
	 * @param DetailCommonLogic               $detailCommonLogic Detail common logic.
	 * @param Module\Carrier\EntityRepository $carrierRepository Carrier repository.
	 * @param Module\FormFactory              $formFactory       Form factory.
	 * @param Repository                      $orderRepository   Order repository.
	 */
	public function __construct(
		Engine $latteEngine,
		DetailCommonLogic $detailCommonLogic,
		Module\Carrier\EntityRepository $carrierRepository,
		Module\FormFactory $formFactory,
		Repository $orderRepository
	) {
		$this->latteEngine       = $latteEngine;
		$this->detailCommonLogic = $detailCommonLogic;
		$this->carrierRepository = $carrierRepository;
		$this->formFactory       = $formFactory;
		$this->orderRepository   = $orderRepository;
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
	 * Available Carriers based on the country of destination.
	 *
	 * @return array|null
	 */
	public function availableCarriers(): ?array {
		$wcOrderId = $this->detailCommonLogic->getOrderid();
		$wcOrder   = $this->orderRepository->getWcOrderById( $wcOrderId );

		if ( null === $wcOrder ) {
			return null;
		}

		$carriers       = $this->carrierRepository->getByCountry( $wcOrder->get_shipping_country() );
		$carrierOptions = [];
		foreach ( $carriers as $carrier ) {
			$carrierOptions[ $carrier->getId() ] = $carrier->getName();
		}

		return $carrierOptions;
	}

	/**
	 * Renders modal.
	 *
	 * @return void
	 */
	public function renderCarrierModal(): void {
		$form              = $this->formFactory->createCarrierChange( $this->availableCarriers() );
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
