<?php

declare( strict_types=1 );

namespace Packetery\Module\Forms;

use Packetery\Module\Email\BugReportEmail;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\MessageManager;
use Packetery\Nette\Forms\Form;

class BugReportForm {

	/** @var WpAdapter */
	private $wpAdapter;

	/** @var MessageManager */
	private $messageManager;

	/** @var BugReportEmail */
	private $bugReportEmail;

	public function __construct(
		WpAdapter $wpAdapter,
		MessageManager $messageManager,
		BugReportEmail $bugReportEmail
	) {
		$this->wpAdapter      = $wpAdapter;
		$this->messageManager = $messageManager;
		$this->bugReportEmail = $bugReportEmail;
	}

	public function createForm(): Form {
		$form = new Form();

		$adminEmail = $this->wpAdapter->getOption( 'admin_email' );
		$form->addEmail( 'replyTo', $this->wpAdapter->__( 'Email for reply', 'packeta' ) )
			->setDefaultValue( is_string( $adminEmail ) ? $adminEmail : '' )
			->setRequired( $this->wpAdapter->__( 'Email is required.', 'packeta' ) )
			->addRule( Form::EMAIL, $this->wpAdapter->__( 'Please enter a valid email address.', 'packeta' ) );

		// Cannot be required because of the WYSIWYG editor.
		$form->addTextArea( 'message', $this->wpAdapter->__( 'Message', 'packeta' ) );

		$form->addCheckbox( 'sendCopy', $this->wpAdapter->__( 'Send copy to sender', 'packeta' ) );

		$form->addSubmit( 'submit', $this->wpAdapter->__( 'Send', 'packeta' ) );

		$form->onSuccess[]  = [ $this, 'onFormSuccess' ];
		$form->onError[]    = [ $this, 'onFormError' ];
		$form->onValidate[] = [ $this, 'onFormValidate' ];

		return $form;
	}

	/**
	 * @param Form                        $form Form instance.
	 * @param array<string, mixed>|object $values Form values (can be ArrayHash from Nette forms).
	 */
	public function onFormSuccess( Form $form, $values ): void {
		$data     = $this->normalizeFormValues( $values );
		$email    = $this->wpAdapter->sanitizeEmail( isset( $data['replyTo'] ) && is_string( $data['replyTo'] ) ? $data['replyTo'] : '' );
		$message  = $this->wpAdapter->wpKsesPost( isset( $data['message'] ) && is_string( $data['message'] ) ? $data['message'] : '' );
		$sendCopy = isset( $data['sendCopy'] ) && is_bool( $data['sendCopy'] ) && $data['sendCopy'];

		$result = $this->bugReportEmail->sendBugReport( $email, $message, $sendCopy );

		if ( $result === true ) {
			$this->messageManager->flash_message(
				$this->wpAdapter->__( 'Bug report sent successfully.', 'packeta' ),
				MessageManager::TYPE_SUCCESS,
				MessageManager::RENDERER_PACKETERY,
				'plugin-options'
			);
		} else {
			$this->messageManager->flash_message(
				$this->wpAdapter->__( 'Failed to send bug report. Please try again.', 'packeta' ),
				MessageManager::TYPE_ERROR,
				MessageManager::RENDERER_PACKETERY,
				'plugin-options'
			);
		}
	}

	public function onFormError( Form $form ): void {
		foreach ( $form->getErrors() as $error ) {
			$this->messageManager->flash_message(
				(string) $error,
				MessageManager::TYPE_ERROR,
				MessageManager::RENDERER_PACKETERY,
				'plugin-options'
			);
		}
	}

	/**
	 * Normalizes form values to array format. Handles both arrays and objects (including ArrayHash from Nette forms).
	 *
	 * @param array<string, mixed>|object $values Form values.
	 * @return array<string, mixed>
	 */
	private function normalizeFormValues( $values ): array {
		if ( is_array( $values ) ) {
			return $values;
		}
		if ( is_object( $values ) ) {
			return (array) $values;
		}

		return [];
	}

	public function onFormValidate( Form $form ): void {
		$valuesArray = $this->normalizeFormValues( $form->getValues() );
		$message     = isset( $valuesArray['message'] ) && is_string( $valuesArray['message'] ) ? $valuesArray['message'] : '';

		if ( trim( $this->wpAdapter->wpStripAllTags( $message ) ) === '' ) {
			$form->addError( $this->wpAdapter->__( 'Message is required.', 'packeta' ) );
		}

		if ( $form->hasErrors() === true ) {
			$this->messageManager->flash_message(
				implode( ', ', $form->getErrors() ),
				MessageManager::TYPE_ERROR,
				MessageManager::RENDERER_PACKETERY,
				'plugin-options'
			);
		}
	}
}
