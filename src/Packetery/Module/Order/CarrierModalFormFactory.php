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
	 * @param string[]    $carrierOptions An array of carrier names.
	 * @param string|null $currentCarrier Current carrier.
	 *
	 * @return Form
	 */
	public function create( array $carrierOptions, ?string $currentCarrier ): Form {
		$form = $this->formFactory->create();

		$form->addSelect( self::FIELD_CARRIER_ID, __( 'Carrier:', 'packeta' ), $carrierOptions )
			->setRequired()
			->setPrompt( __( 'Pick a carrier', 'packeta' ) );

		$form->addHidden( self::FIELD_NONCE );

		foreach ( $carrierOptions as $carrierId => $carrierName ) {
			if ( $carrierId === $currentCarrier ) {
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

}
