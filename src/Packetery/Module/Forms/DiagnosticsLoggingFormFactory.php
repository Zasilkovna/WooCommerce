<?php

namespace Packetery\Module\Forms;

use Packetery\Module\FormFactory;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionNames;
use Packetery\Nette\Forms\Controls\SubmitButton;
use Packetery\Nette\Forms\Form;

class DiagnosticsLoggingFormFactory {
	/**
	 * @var FormFactory
	 */
	private $formFactory;

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	public function __construct(
		FormFactory $formFactory,
		WpAdapter $wpAdapter
	) {
		$this->formFactory = $formFactory;
		$this->wpAdapter   = $wpAdapter;
	}

	public function createForm(): Form {
		$form = $this->formFactory->create();
		$form->addCheckbox( 'enabled', __( 'Enable logging', 'packeta' ) );
		$form->addSubmit( 'save', __( 'Save', 'packeta' ) );
		$form->onSuccess[] = [ $this, 'onFormSuccess' ];

		if ( $form['save'] instanceof SubmitButton &&
			$form['save']->isSubmittedBy() === false
		) {
			$form->setValues(
				[
					'enabled' => $this->wpAdapter->getOption( OptionNames::PACKETERY_DIAGNOSTICS_LOGGING_ENABLED ),
				],
				true
			);
		}

		return $form;
	}

	public function onFormSuccess( Form $form ): void {
		/** @var array{enabled: bool} $values */
		$values = $form->getValues( 'array' );
		$this->wpAdapter->updateOption( OptionNames::PACKETERY_DIAGNOSTICS_LOGGING_ENABLED, $values['enabled'] );
	}
}
