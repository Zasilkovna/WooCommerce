<?php
/**
 * Class CarrierModalFormFactory
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Entity;
use Packetery\Nette\Forms\Form;
use Packetery\Module\FormFactory;

/**
 * Class CarrierModalFormFactory
 *
 * @package Packetery
 */
class CarrierModalFormFactory {

	public const FIELD_CARRIER_ID = 'carrierId';
	private const FIELD_NONCE     = 'packetery_carrier_metabox_nonce';

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
	 * @param Entity\Carrier[] $carriers       An array of carrier data.
	 * @param string|null      $currentCarrier Current carrier.
	 *
	 * @return Form
	 */
	public function create( array $carriers, ?string $currentCarrier ): Form {
		$form = $this->formFactory->create();

		$form->addSelect( self::FIELD_CARRIER_ID, __( 'Carrier:', 'packeta' ), $this->getAvailableCarrierOptions( $carriers ) )
			->setRequired()
			->setPrompt( 'Pick a carrier' );

		$form->addHidden( self::FIELD_NONCE );

		foreach ( $carriers as $carrier ) {
			if ( $carrier->getId() === $currentCarrier ) {
				$form->setDefaults(
					[
						self::FIELD_NONCE      => wp_create_nonce(),
						self::FIELD_CARRIER_ID => $currentCarrier,
					]
				);
				break;
			}
		}

		$form->addSubmit( 'submit', __( 'Save', 'packeta' ) );
		$form->addSubmit( 'cancel', __( 'Cancel', 'packeta' ) );

		return $form;
	}

	/**
	 * Available Carriers based on the country of destination.
	 *
	 * @param Entity\Carrier[] $carriers An array of carrier data.
	 *
	 * @return array|null
	 */
	public function getAvailableCarrierOptions( array $carriers ): ?array {
		$carrierOptions = [];
		foreach ( $carriers as $carrier ) {
			$carrierOptions[ $carrier->getId() ] = $carrier->getName();
		}

		return $carrierOptions;
	}
}
