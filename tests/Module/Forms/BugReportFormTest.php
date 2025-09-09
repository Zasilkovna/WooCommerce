<?php

declare( strict_types=1 );

namespace Tests\Module\Forms;

use Packetery\Module\Email\BugReportEmail;
use Packetery\Module\Forms\BugReportForm;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\MessageManager;
use Packetery\Nette\Forms\Controls\BaseControl;
use Packetery\Nette\Forms\Form;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BugReportFormTest extends TestCase {
	private WpAdapter&MockObject $wpAdapter;
	private MessageManager&MockObject $messageManager;
	private BugReportEmail&MockObject $bugReportEmail;

	private const TEST_EMAIL       = 'test@example.com';
	private const TEST_SITE_NAME   = 'Test Site';
	private const TEST_MESSAGE     = 'Test bug report message';
	private const ADMIN_EMAIL      = 'admin@example.com';
	private const TEXT_DOMAIN      = 'packeta';
	private const MESSAGE_REQUIRED = 'Message is required.';

	private function createBugReportForm(): BugReportForm {
		$this->wpAdapter      = $this->createMock( WpAdapter::class );
		$this->messageManager = $this->createMock( MessageManager::class );
		$this->bugReportEmail = $this->createMock( BugReportEmail::class );

		return new BugReportForm(
			$this->wpAdapter,
			$this->messageManager,
			$this->bugReportEmail
		);
	}

	public function testCreateForm(): void {
		$bugReportForm = $this->createBugReportForm();

		$this->wpAdapter->method( '__' )
			->willReturnCallback(
				function ( $text ) {
					return $text;
				}
			);

		$this->wpAdapter->method( 'getOption' )
			->with( 'admin_email' )
			->willReturn( self::ADMIN_EMAIL );

		$form = $bugReportForm->createForm();

		$this->assertNotNull( $form->getComponent( 'replyTo' ) );
		$this->assertNotNull( $form->getComponent( 'message' ) );
		$this->assertNotNull( $form->getComponent( 'submit' ) );
	}

	public function testOnFormSuccessWithValidData(): void {
		$bugReportForm = $this->createBugReportForm();

		$formMock = $this->createMock( Form::class );
		$values   = [
			'replyTo' => self::TEST_EMAIL,
			'message' => self::TEST_MESSAGE,
		];

		$this->wpAdapter->method( '__' )
			->willReturnCallback(
				function ( $text ) {
					return $text;
				}
			);

		$this->wpAdapter->method( 'sanitizeEmail' )
			->with( self::TEST_EMAIL )
			->willReturn( self::TEST_EMAIL );

		$this->wpAdapter->method( 'wpKsesPost' )
			->with( self::TEST_MESSAGE )
			->willReturn( self::TEST_MESSAGE );

		$this->bugReportEmail->method( 'sendBugReport' )
			->willReturn( true );

		$this->messageManager->expects( $this->once() )
			->method( 'flash_message' )
			->with(
				$this->anything(),
				MessageManager::TYPE_SUCCESS,
				MessageManager::RENDERER_PACKETERY,
				'plugin-options'
			);

		$bugReportForm->onFormSuccess( $formMock, $values );
	}

	public function testOnFormSuccessWithFailedEmail(): void {
		$bugReportForm = $this->createBugReportForm();

		$formMock = $this->createMock( Form::class );
		$values   = [
			'replyTo' => self::TEST_EMAIL,
			'message' => self::TEST_MESSAGE,
		];

		$this->wpAdapter->method( '__' )
			->willReturnCallback(
				function ( $text ) {
					return $text;
				}
			);

		$this->wpAdapter->method( 'sanitizeEmail' )
			->with( self::TEST_EMAIL )
			->willReturn( self::TEST_EMAIL );

		$this->wpAdapter->method( 'wpKsesPost' )
			->with( self::TEST_MESSAGE )
			->willReturn( self::TEST_MESSAGE );

		$this->bugReportEmail->method( 'sendBugReport' )
			->willReturn( false );

		$this->messageManager->expects( $this->once() )
			->method( 'flash_message' )
			->with(
				$this->anything(),
				MessageManager::TYPE_ERROR,
				MessageManager::RENDERER_PACKETERY,
				'plugin-options'
			);

		$bugReportForm->onFormSuccess( $formMock, $values );
	}

	public function testOnFormError(): void {
		$bugReportForm = $this->createBugReportForm();

		$formMock = $this->createMock( Form::class );
		$errors   = [
			'email'   => 'Email is required.',
			'message' => 'Message is required.',
		];

		$formMock->method( 'getErrors' )
			->willReturn( $errors );

		$this->messageManager->expects( $this->exactly( 2 ) )
			->method( 'flash_message' )
			->with(
				$this->logicalOr(
					$this->equalTo( $errors['email'] ),
					$this->equalTo( $errors['message'] )
				),
				MessageManager::TYPE_ERROR,
				MessageManager::RENDERER_PACKETERY,
				'plugin-options'
			);

		$bugReportForm->onFormError( $formMock );
	}

	public function testOnFormValidateWithEmptyMessage(): void {
		$bugReportForm = $this->createBugReportForm();

		$formMock         = $this->createMock( Form::class );
		$messageFieldMock = $this->createMock( BaseControl::class );
		$values           = [
			'replyTo' => self::TEST_EMAIL,
			'message' => '',
		];

		$formMock->method( 'getValues' )
			->willReturn( $values );

		$formMock->method( 'offsetGet' )
			->with( 'message' )
			->willReturn( $messageFieldMock );

		$this->wpAdapter->method( 'wpStripAllTags' )
			->with( '' )
			->willReturn( '' );

		$this->wpAdapter->method( '__' )
			->with( self::MESSAGE_REQUIRED, self::TEXT_DOMAIN )
			->willReturn( self::MESSAGE_REQUIRED );

		$messageFieldMock->expects( $this->once() )
			->method( 'addError' )
			->with( self::MESSAGE_REQUIRED );

		$bugReportForm->onFormValidate( $formMock );
	}

	public function testOnFormValidateWithValidMessage(): void {
		$bugReportForm = $this->createBugReportForm();

		$formMock         = $this->createMock( Form::class );
		$messageFieldMock = $this->createMock( BaseControl::class );
		$validMessage     = 'This is a valid message';
		$values           = [
			'replyTo' => self::TEST_EMAIL,
			'message' => $validMessage,
		];

		$formMock->method( 'getValues' )
			->willReturn( $values );

		$formMock->method( 'offsetGet' )
			->with( 'message' )
			->willReturn( $messageFieldMock );

		$this->wpAdapter->method( 'wpStripAllTags' )
			->with( $validMessage )
			->willReturn( $validMessage );

		$messageFieldMock->expects( $this->never() )
			->method( 'addError' );

		$bugReportForm->onFormValidate( $formMock );
	}
}
