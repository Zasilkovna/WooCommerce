<?php
/**
 * Class CarrierModalFormFactory
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Nette\Forms\Form;
use Packetery\Module\FormFactory;
use Packetery\Module\Carrier\EntityRepository;

/**
 * Class CarrierModalFormFactory
 *
 * @package Packetery
 */
class CarrierModalFormFactory {

	/**
	 * Carrier repository.
	 *
	 * @var EntityRepository
	 */
	private $carrierRepository;

	/**
	 * Order repository.
	 *
	 * @var Repository
	 */
	private $orderRepository;

	/**
	 * Order detail common logic.
	 *
	 * @var DetailCommonLogic
	 */
	private $detailCommonLogic;

	/**
	 * Class FormFactory
	 *
	 * @var FormFactory
	 */
	private $formFactory;

	/**
	 * FormFactory constructor
	 *
	 * @param FormFactory       $formFactory       Form factory.
	 * @param DetailCommonLogic $detailCommonLogic Detail common logic.
	 * @param Repository        $orderRepository   Order repository.
	 * @param EntityRepository  $carrierRepository Carrier repository.
	 */
	public function __construct( FormFactory $formFactory, DetailCommonLogic $detailCommonLogic, Repository $orderRepository, EntityRepository $carrierRepository ) {
		$this->formFactory       = $formFactory;
		$this->detailCommonLogic = $detailCommonLogic;
		$this->orderRepository   = $orderRepository;
		$this->carrierRepository = $carrierRepository;
	}

	/**
	 * Creating a form
	 *
	 * @return Form
	 */
	public function createCarrierChange(): Form {
		$form = $this->formFactory->create();
		$form->addHidden( 'packetery_carrier_metabox_nonce' );
		$form->setDefaults( [ 'packetery_carrier_metabox_nonce' => wp_create_nonce() ] );
		$form->addSelect( 'carrierId', __( 'Carrier:', 'packeta' ), $this->availableCarriers() )
			->setRequired()
			->setPrompt( 'Pick a carrier' );

		$form->addSubmit( 'submit', __( 'Save', 'packeta' ) );
		$form->addSubmit( 'cancel', __( 'Cancel', 'packeta' ) );

		return $form;
	}

	/**
	 * Available Carriers based on the country of destination.
	 *
	 * @return array|null
	 */
	private function availableCarriers(): ?array {
		$wcOrderId = $this->detailCommonLogic->getOrderid();
		if ( null === $wcOrderId ) {
			return null;
		}

		$wcOrder = $this->orderRepository->getWcOrderById( $wcOrderId );
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
}
