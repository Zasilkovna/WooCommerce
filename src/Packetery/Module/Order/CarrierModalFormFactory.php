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

/**
 * Class CarrierModalFormFactory
 *
 * @package Packetery
 */
class CarrierModalFormFactory {
	/**
	 * Class FormFactory
	 *
	 * @var FormFactory
	 */
	private $formFactory;

	/**
	 * FormFactory constructor
	 *
	 * @param FormFactory $formFactory Form factory.
	 */
	public function __construct( FormFactory $formFactory ) {
		$this->formFactory = $formFactory;
	}

	/**
	 * Creating a form
	 *
	 * @param array $carriers An array of carrier data.
	 * @return Form
	 */
	public function create( array $carriers ): Form {
		$form = $this->formFactory->create();
		$form->addHidden( 'packetery_carrier_metabox_nonce' );
		$form->setDefaults( [ 'packetery_carrier_metabox_nonce' => wp_create_nonce() ] );
		$form->addSelect( 'carrierId', __( 'Carrier:', 'packeta' ), $this->getAvailableCarrierOptions( $carriers ) )
			->setRequired()
			->setPrompt( 'Pick a carrier' );

		$form->addSubmit( 'submit', __( 'Save', 'packeta' ) );
		$form->addSubmit( 'cancel', __( 'Cancel', 'packeta' ) );

		return $form;
	}

	/**
	 * Available Carriers based on the country of destination.
	 *
	 * @param array $carriers An array of carrier data.
	 * @return array|null
	 */
	public function getAvailableCarrierOptions(array $carriers ): ?array {
		$carrierOptions = [];
		foreach ( $carriers as $carrier ) {
			$carrierOptions[ $carrier['id'] ] = $carrier['name'];
		}

		return $carrierOptions;
	}
}
