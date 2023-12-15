<?php
/**
 * Class CarrierModal.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Entity\Carrier;
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
	 * Carrier repository.
	 *
	 * @var Module\Carrier\Repository
	 */
	private $carrierRepository;

	/**
	 * Order repository.
	 *
	 * @var Repository
	 */
	private $orderRepository;

	/**
	 * Constructor.
	 *
	 * @param Engine                    $latteEngine             Latte engine.
	 * @param DetailCommonLogic         $detailCommonLogic       Detail common logic.
	 * @param CarrierModalFormFactory   $carrierModalFormFactory Carrier Modal form factory.
	 * @param Repository                $orderRepository         Order repository.
	 * @param Module\Carrier\Repository $carrierRepository       Carrier repository.
	 */
	public function __construct(
		Engine $latteEngine,
		DetailCommonLogic $detailCommonLogic,
		CarrierModalFormFactory $carrierModalFormFactory,
		Repository $orderRepository,
		Module\Carrier\Repository $carrierRepository
	) {
		$this->latteEngine             = $latteEngine;
		$this->detailCommonLogic       = $detailCommonLogic;
		$this->carrierModalFormFactory = $carrierModalFormFactory;
		$this->orderRepository         = $orderRepository;
		$this->carrierRepository       = $carrierRepository;
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
		if ( false === $this->detailCommonLogic->isPacketeryOrder()
		) {
			return;
		}

		$form              = $this->carrierModalFormFactory->create( $this->getCarriersByCountry() );
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

	/**
	 * Gets Carriers by the country of destination.
	 *
	 * @return array|null
	 */
	private function getCarriersByCountry(): ?array {
		$wcOrderId = $this->detailCommonLogic->getOrderid();
		if ( null === $wcOrderId ) {
			return null;
		}

		$wcOrder = $this->orderRepository->getWcOrderById( $wcOrderId );
		if ( null === $wcOrder ) {
			return null;
		}

		$shippingCountry = $wcOrder->get_shipping_country();
		if ( null === $shippingCountry ) {
			return null;
		}

		return $this->carrierRepository->getByCountry( $shippingCountry );
	}
}
