<?php

declare( strict_types=1 );

namespace Packetery\Module\Options;

use Packetery\Latte\Engine;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\MessageManager;
use Packetery\Module\Options\Exporter;
use Packetery\Nette\Forms\Container;
use Packetery\Nette\Forms\Controls\TextArea;
use Packetery\Nette\Forms\Form;
use ZipArchive;

class BugReportService {

	/** @var WpAdapter */
	private $wpAdapter;

	/** @var WcAdapter */
	private $wcAdapter;

	/** @var Exporter */
	private $exporter;

	/** @var string */
	private $supportEmailAddress;

	/** @var Engine */
	private $latteEngine;

	/** @var MessageManager */
	private $messageManager;

	public function __construct(
		WpAdapter $wpAdapter,
		WcAdapter $wcAdapter,
		Exporter $exporter,
		string $supportEmailAddress,
		Engine $latteEngine,
		MessageManager $messageManager
	) {
		$this->wpAdapter           = $wpAdapter;
		$this->wcAdapter           = $wcAdapter;
		$this->exporter            = $exporter;
		$this->supportEmailAddress = $supportEmailAddress;
		$this->latteEngine         = $latteEngine;
		$this->messageManager      = $messageManager;
	}

	public function createForm(): Form {
		$form = new Form();

		$adminEmail = $this->wpAdapter->getOption( 'admin_email' );
		$form->addEmail( 'replyTo', $this->wpAdapter->__( 'Email for reply', 'packeta' ) )
			->setDefaultValue( is_string( $adminEmail ) ? $adminEmail : '' )
			->setRequired( (string) $this->wpAdapter->__( 'Email is required.', 'packeta' ) )
			->addRule( Form::EMAIL, $this->wpAdapter->__( 'Please enter a valid email address.', 'packeta' ) );

		$form->addTextArea( 'message', $this->wpAdapter->__( 'Message', 'packeta' ) );

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
		$data    = $this->normalizeFormValues( $values );
		$email   = $this->wpAdapter->sanitizeEmail( isset( $data['replyTo'] ) && is_string( $data['replyTo'] ) ? $data['replyTo'] : '' );
		$message = $this->wpAdapter->wpKsesPost( isset( $data['message'] ) && is_string( $data['message'] ) ? $data['message'] : '' );

		$result = $this->sendBugReport( $email, $message );

		if ( $result ) {
			$this->messageManager->flash_message(
				(string) $this->wpAdapter->__( 'Bug report sent successfully.', 'packeta' ),
				MessageManager::TYPE_SUCCESS,
				MessageManager::RENDERER_PACKETERY,
				'plugin-options'
			);
		} else {
			$this->messageManager->flash_message(
				(string) $this->wpAdapter->__( 'Failed to send bug report. Please try again.', 'packeta' ),
				MessageManager::TYPE_ERROR,
				MessageManager::RENDERER_PACKETERY,
				'plugin-options'
			);
		}
	}

	/**
	 * @param Form $form
	 */
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

	/**
	 * @param Container $form
	 */
	public function onFormValidate( Container $form ): void {
		$valuesArray = $this->normalizeFormValues( $form->getValues() );
		$message     = isset( $valuesArray['message'] ) && is_string( $valuesArray['message'] ) ? $valuesArray['message'] : '';

		if ( trim( $this->wpAdapter->wpStripAllTags( $message ) ) === '' ) {
			/** @var TextArea $messageControl */
			$messageControl = $form['message'];
			$messageControl->addError( (string) $this->wpAdapter->__( 'Message is required.', 'packeta' ) );
		}
	}

	private function sendBugReport( string $replyEmail, string $message ): bool {
		$siteName = $this->wpAdapter->getBlogInfo( 'name', 'raw' );
		// translators: %s is site name
		$subject = sprintf( (string) $this->wpAdapter->__( 'Packeta: Plugin WP - bug report - %s', 'packeta' ), $siteName );

		/**
		 * @var non-empty-list<string> $headers
		 */
		$headers = [
			'Content-Type: text/html; charset=UTF-8',
			"From: {$replyEmail}",
			"Reply-To: {$replyEmail}",
		];

		return $this->wpAdapter->wpMail(
			$this->supportEmailAddress,
			$subject,
			$this->createEmailBody( $replyEmail, $message, $siteName ),
			$headers,
			$this->createAttachments()
		);
	}

	private function createEmailBody( string $replyEmail, string $message, string $siteName ): string {
		return $this->latteEngine->renderToString(
			PACKETERY_PLUGIN_DIR . '/template/email/bug-report.latte',
			[
				'replyEmail'          => $replyEmail,
				'message'             => $message,
				'siteName'            => $siteName,
				'zipArchiveAvailable' => class_exists( 'ZipArchive' ),
				'translations'        => [
					'bugReportTitle'          => $this->wpAdapter->__( 'Bug report from Packeta plugin', 'packeta' ),
					'website'                 => $this->wpAdapter->__( 'Website:', 'packeta' ),
					'replyEmail'              => $this->wpAdapter->__( 'Email for reply:', 'packeta' ),
					'message'                 => $this->wpAdapter->__( 'Message:', 'packeta' ),
					'autoGenerated'           => $this->wpAdapter->__( 'This report was automatically generated by the Packeta WordPress plugin.', 'packeta' ),
					'attachmentsNotAvailable' => $this->wpAdapter->__( 'Attachments could not be added to the email because the e-shop server is missing the extension required to create a ZIP file.', 'packeta' ),
				],
			]
		);
	}

	/**
	 * @return string[]
	 */
	private function createAttachments(): array {
		$attachments = [];
		if ( ! class_exists( 'ZipArchive' ) ) {
			return $attachments;
		}

		$timestamp   = $this->wpAdapter->currentTime( 'Y-m-d_H-i-s', false );
		$zipFilename = "logs-{$timestamp}.zip";

		$zip     = new ZipArchive();
		$zipPath = sys_get_temp_dir() . '/' . $zipFilename;

		if ( $zip->open( $zipPath, ZipArchive::CREATE ) === true ) {
			$settingsContent = $this->exporter->getExportContent();
			$zip->addFromString( 'packeta-settings.txt', $settingsContent );
			$this->addWooCommerceSystemStatusToZip( $zip );
			$zip->close();

			if ( file_exists( $zipPath ) ) {
				$attachments[] = $zipPath;
			}
		}

		return $attachments;
	}

	private function addWooCommerceSystemStatusToZip( ZipArchive $zip ): void {
		if ( ! class_exists( 'WC_Admin_Status' ) ) {
			return;
		}

		ob_start();
		$this->wcAdapter->adminStatusStatusReport();
		$systemStatus = ob_get_clean();

		if ( $systemStatus !== false && $systemStatus !== '' ) {
			$zip->addFromString( 'woocommerce-system-status.html', $systemStatus );
		}
	}
}
