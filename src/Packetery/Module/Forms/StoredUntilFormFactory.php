<?php

declare( strict_types=1 );

namespace Packetery\Module\Forms;

use Packetery\Module\FormFactory;
use Packetery\Nette\Forms\Form;

class StoredUntilFormFactory {

	/**
	 * Form factory.
	 *
	 * @var FormFactory
	 */
	private $formFactory;

	/**
	 * Constructor.
	 *
	 * @param FormFactory $formFactory Form factory.
	 */
	public function __construct(
		FormFactory $formFactory
	) {
		$this->formFactory = $formFactory;
	}

	/**
	 * Creates stored until modal form.
	 *
	 * @param string $name     Form name.
	 *
	 * @return Form
	 */
	public function createForm( string $name ): Form {
		$form = $this->formFactory->create( $name );
		$form->addHidden( 'packet_id' );
		$form->addText( 'packetery_stored_until', __( 'Planned dispatch', 'packeta' ) )
			->setHtmlAttribute( 'class', 'date-picker' )
			->setHtmlAttribute( 'autocomplete', 'off' )
			->setRequired( false )
			->setNullable();
		$form->addSubmit( 'submit', __( 'Change', 'packeta' ) );
		$form->addButton( 'cancel', __( 'Cancel', 'packeta' ) );

		return $form;
	}
}
