<?php

namespace Packetery\Module\Forms;

use Packetery\Module\FormFactory;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\MessageManager;
use Packetery\Module\Options\OptionNames;
use Packetery\Nette\Forms\Controls\SubmitButton;
use Packetery\Nette\Forms\Form;

class DiagnosticsLoggingFormFactory {
	/** @var FormFactory */
	private $formFactory;

	/** @var WpAdapter */
	private $wpAdapter;

	/** @var MessageManager */
	private $messageManager;

	public function __construct(
		FormFactory $formFactory,
		WpAdapter $wpAdapter,
		MessageManager $messageManager
	) {
		$this->formFactory    = $formFactory;
		$this->wpAdapter      = $wpAdapter;
		$this->messageManager = $messageManager;
	}

	public function createForm(): Form {
		$form = $this->formFactory->create();
		$form->addCheckbox( 'enabled', __( 'Enable logging', 'packeta' ) );
		$form->addSubmit( 'saveDiagnosticsLog', __( 'Save', 'packeta' ) );
		$form->onSuccess[] = [ $this, 'onFormSuccess' ];

		if ( $form['saveDiagnosticsLog'] instanceof SubmitButton &&
			$form['saveDiagnosticsLog']->isSubmittedBy() === false
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

		$this->messageManager->flash_message(
			$this->wpAdapter->__( 'Settings saved.', 'packeta' ),
			MessageManager::TYPE_SUCCESS,
			MessageManager::RENDERER_PACKETERY,
			'plugin-options'
		);
	}
}
