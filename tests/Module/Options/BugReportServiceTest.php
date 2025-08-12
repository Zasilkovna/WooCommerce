<?php

declare( strict_types=1 );

namespace Tests\Module\Options;

use Packetery\Latte\Engine;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\MessageManager;
use Packetery\Module\Options\BugReportService;
use Packetery\Module\Options\Exporter;
use Packetery\Nette\Forms\Controls\BaseControl;
use Packetery\Nette\Forms\Form;
use PHPUnit\Framework\TestCase;

class BugReportServiceTest extends TestCase {
	private const TEST_EMAIL          = 'test@example.com';
	private const TEST_SITE_NAME      = 'Test Site';
	private const TEST_TIMESTAMP      = '2024-01-01_12-00-00';
	private const TEST_EXPORT_CONTENT = 'exported settings content';
	private const TEST_EMAIL_BODY     = 'email body content';
	private const SUCCESS_MESSAGE     = 'Bug report sent successfully.';
	private const ERROR_MESSAGE       = 'Failed to send bug report. Please try again.';
	private const TEST_MESSAGE        = 'Test bug report message';
	private const ADMIN_EMAIL         = 'admin@example.com';
	private const CUSTOM_EMAIL        = 'custom@example.com';
	private const TEXT_DOMAIN         = 'packeta';
	private const REPLY_EMAIL         = 'Reply email';
	private const EMAIL_REQUIRED      = 'Email is required.';
	private const EMAIL_INVALID       = 'Please enter a valid email address.';
	private const MESSAGE_LABEL       = 'Message';
	private const MESSAGE_REQUIRED    = 'Message is required.';

	private const SEND_BUTTON = 'Send';

	private BugReportService $bugReportService;
	private WpAdapter $wpAdapter;
	private WcAdapter $wcAdapter;
	private Exporter $exporter;
	private Engine $latteEngine;
	private MessageManager $messageManager;

	protected function setUp(): void {
		parent::setUp();

		$this->wpAdapter      = $this->createMock( WpAdapter::class );
		$this->wcAdapter      = $this->createMock( WcAdapter::class );
		$this->exporter       = $this->createMock( Exporter::class );
		$this->latteEngine    = $this->createMock( Engine::class );
		$this->messageManager = $this->createMock( MessageManager::class );

		$this->bugReportService = new BugReportService(
			$this->wpAdapter,
			$this->wcAdapter,
			$this->exporter,
			'support@packeta.com',
			$this->latteEngine,
			$this->messageManager
		);
	}

	public function testCreateForm(): void {
		$this->wpAdapter->method( '__' )
			->willReturnMap(
				[
					[ self::REPLY_EMAIL, self::TEXT_DOMAIN, self::REPLY_EMAIL ],
					[ self::EMAIL_REQUIRED, self::TEXT_DOMAIN, self::EMAIL_REQUIRED ],
					[ self::EMAIL_INVALID, self::TEXT_DOMAIN, self::EMAIL_INVALID ],
					[ self::MESSAGE_LABEL, self::TEXT_DOMAIN, self::MESSAGE_LABEL ],
					[ self::MESSAGE_REQUIRED, self::TEXT_DOMAIN, self::MESSAGE_REQUIRED ],
					[ self::SEND_BUTTON, self::TEXT_DOMAIN, self::SEND_BUTTON ],
					[ self::SUCCESS_MESSAGE, self::TEXT_DOMAIN, self::SUCCESS_MESSAGE ],
					[ self::ERROR_MESSAGE, self::TEXT_DOMAIN, self::ERROR_MESSAGE ],
				]
			);

		$this->wpAdapter->method( 'getOption' )
			->with( 'admin_email' )
			->willReturn( self::ADMIN_EMAIL );

		$form = $this->bugReportService->createForm();

		$this->assertInstanceOf( Form::class, $form );
		$this->assertInstanceOf( Form::class, $form );
		$this->assertNotNull( $form->getComponent( 'replyTo' ) );
		$this->assertNotNull( $form->getComponent( 'message' ) );
		$this->assertNotNull( $form->getComponent( 'submit' ) );
	}

	public function testOnFormSuccessWithValidData(): void {
		$form   = $this->createMock( Form::class );
		$values = [
			'replyTo' => self::TEST_EMAIL,
			'message' => self::TEST_MESSAGE,
		];

		$this->wpAdapter->method( '__' )
			->willReturnMap(
				[
					[ self::SUCCESS_MESSAGE, self::TEXT_DOMAIN, self::SUCCESS_MESSAGE ],
				]
			);

		$this->wpAdapter->method( 'sanitizeEmail' )
			->with( self::TEST_EMAIL )
			->willReturn( self::TEST_EMAIL );

		$this->wpAdapter->method( 'wpKsesPost' )
			->with( self::TEST_MESSAGE )
			->willReturn( self::TEST_MESSAGE );

		$this->wpAdapter->method( 'getBlogInfo' )
			->with( 'name', 'raw' )
			->willReturn( self::TEST_SITE_NAME );

		$this->wpAdapter->method( 'currentTime' )
			->with( 'Y-m-d_H-i-s', false )
			->willReturn( self::TEST_TIMESTAMP );

		$this->wpAdapter->method( 'wpMail' )
			->willReturn( true );

		$this->exporter->method( 'getExportContent' )
			->willReturn( self::TEST_EXPORT_CONTENT );

		$this->latteEngine->method( 'renderToString' )
			->willReturn( self::TEST_EMAIL_BODY );

		$this->messageManager->expects( $this->once() )
			->method( 'flash_message' )
			->with(
				$this->anything(),
				MessageManager::TYPE_SUCCESS,
				MessageManager::RENDERER_PACKETERY,
				'plugin-options'
			);

		$this->bugReportService->onFormSuccess( $form, $values );
	}

	public function testOnFormSuccessWithFailedEmail(): void {
		$form   = $this->createMock( Form::class );
		$values = [
			'replyTo' => self::TEST_EMAIL,
			'message' => self::TEST_MESSAGE,
		];

		$this->wpAdapter->method( '__' )
			->willReturnMap(
				[
					[ self::ERROR_MESSAGE, self::TEXT_DOMAIN, self::ERROR_MESSAGE ],
				]
			);

		$this->wpAdapter->method( 'sanitizeEmail' )
			->with( self::TEST_EMAIL )
			->willReturn( self::TEST_EMAIL );

		$this->wpAdapter->method( 'wpKsesPost' )
			->with( self::TEST_MESSAGE )
			->willReturn( self::TEST_MESSAGE );

		$this->wpAdapter->method( 'getBlogInfo' )
			->with( 'name', 'raw' )
			->willReturn( self::TEST_SITE_NAME );

		$this->wpAdapter->method( 'currentTime' )
			->with( 'Y-m-d_H-i-s', false )
			->willReturn( self::TEST_TIMESTAMP );

		$this->wpAdapter->method( 'wpMail' )
			->willReturn( false );

		$this->exporter->method( 'getExportContent' )
			->willReturn( self::TEST_EXPORT_CONTENT );

		$this->latteEngine->method( 'renderToString' )
			->willReturn( self::TEST_EMAIL_BODY );

		$this->messageManager->expects( $this->once() )
			->method( 'flash_message' )
			->with(
				$this->anything(),
				MessageManager::TYPE_ERROR,
				MessageManager::RENDERER_PACKETERY,
				'plugin-options'
			);

		$this->bugReportService->onFormSuccess( $form, $values );
	}

	public function testOnFormError(): void {
		$form   = $this->createMock( Form::class );
		$errors = [
			'Email is required.',
			'Message is required.',
		];

		$form->method( 'getErrors' )
			->willReturn( $errors );

		$this->messageManager->expects( $this->exactly( 2 ) )
			->method( 'flash_message' )
			->with(
				$this->logicalOr(
					$this->equalTo( 'Email is required.' ),
					$this->equalTo( 'Message is required.' )
				),
				MessageManager::TYPE_ERROR,
				MessageManager::RENDERER_PACKETERY,
				'plugin-options'
			);

		$this->bugReportService->onFormError( $form );
	}

	public function testOnFormSuccessWithSpecialCharacters(): void {
		$form   = $this->createMock( Form::class );
		$values = [
			'replyTo' => 'test+tag@example.com',
			'message' => 'Test message with <script>alert("xss")</script> and special chars: áčďéěíňóřšťúůýž',
		];

		$this->wpAdapter->method( 'sanitizeEmail' )
			->with( 'test+tag@example.com' )
			->willReturn( 'test+tag@example.com' );

		$this->wpAdapter->method( 'wpKsesPost' )
			->with( 'Test message with <script>alert("xss")</script> and special chars: áčďéěíňóřšťúůýž' )
			->willReturn( 'Test message with <script>alert("xss")</script> and special chars: áčďéěíňóřšťúůýž' );

		$this->wpAdapter->method( 'getBlogInfo' )
			->with( 'name', 'raw' )
			->willReturn( 'Test Site with Čechy' );

		$this->wpAdapter->method( 'currentTime' )
			->with( 'Y-m-d_H-i-s', false )
			->willReturn( self::TEST_TIMESTAMP );

		$this->wpAdapter->method( 'wpMail' )
			->willReturn( true );

		$this->exporter->method( 'getExportContent' )
			->willReturn( self::TEST_EXPORT_CONTENT );

		$this->latteEngine->method( 'renderToString' )
			->willReturn( self::TEST_EMAIL_BODY );

		$this->wpAdapter->method( '__' )
			->willReturnMap(
				[
					[ self::SUCCESS_MESSAGE, self::TEXT_DOMAIN, self::SUCCESS_MESSAGE ],
				]
			);

		$this->messageManager->expects( $this->once() )
			->method( 'flash_message' )
			->with(
				$this->anything(),
				MessageManager::TYPE_SUCCESS,
				MessageManager::RENDERER_PACKETERY,
				'plugin-options'
			);

		$this->bugReportService->onFormSuccess( $form, $values );
	}

	public function testCreateFormWithCustomAdminEmail(): void {
		$this->wpAdapter->method( '__' )
			->willReturnMap(
				[
					[ self::REPLY_EMAIL, self::TEXT_DOMAIN, self::REPLY_EMAIL ],
					[ self::EMAIL_REQUIRED, self::TEXT_DOMAIN, self::EMAIL_REQUIRED ],
					[ self::EMAIL_INVALID, self::TEXT_DOMAIN, self::EMAIL_INVALID ],
					[ self::MESSAGE_LABEL, self::TEXT_DOMAIN, self::MESSAGE_LABEL ],
					[ self::MESSAGE_REQUIRED, self::TEXT_DOMAIN, self::MESSAGE_REQUIRED ],
					[ self::SEND_BUTTON, self::TEXT_DOMAIN, self::SEND_BUTTON ],
				]
			);

		$this->wpAdapter->method( 'getOption' )
			->with( 'admin_email' )
			->willReturn( self::CUSTOM_EMAIL );

		$form = $this->bugReportService->createForm();

		$this->assertInstanceOf( Form::class, $form );
		$this->assertNotNull( $form->getComponent( 'replyTo' ) );
		$this->assertNotNull( $form->getComponent( 'message' ) );
		$this->assertNotNull( $form->getComponent( 'submit' ) );

		$this->assertInstanceOf( Form::class, $form );
	}

	public function testCreateFormWithEmptyAdminEmail(): void {
		$this->wpAdapter->method( '__' )
			->willReturnMap(
				[
					[ self::REPLY_EMAIL, self::TEXT_DOMAIN, self::REPLY_EMAIL ],
					[ self::EMAIL_REQUIRED, self::TEXT_DOMAIN, self::EMAIL_REQUIRED ],
					[ self::EMAIL_INVALID, self::TEXT_DOMAIN, self::EMAIL_INVALID ],
					[ self::MESSAGE_LABEL, self::TEXT_DOMAIN, self::MESSAGE_LABEL ],
					[ self::MESSAGE_REQUIRED, self::TEXT_DOMAIN, self::MESSAGE_REQUIRED ],
					[ self::SEND_BUTTON, self::TEXT_DOMAIN, self::SEND_BUTTON ],
				]
			);

		$this->wpAdapter->method( 'getOption' )
			->with( 'admin_email' )
			->willReturn( '' );

		$form = $this->bugReportService->createForm();

		$this->assertInstanceOf( Form::class, $form );
		$this->assertNotNull( $form->getComponent( 'replyTo' ) );
		$this->assertNotNull( $form->getComponent( 'message' ) );
		$this->assertNotNull( $form->getComponent( 'submit' ) );

		$this->assertInstanceOf( Form::class, $form );
	}

	public function testOnFormSuccessWithLongMessage(): void {
		$form        = $this->createMock( Form::class );
		$longMessage = str_repeat( 'This is a very long message that tests the handling of large content. ', 100 );
		$values      = [
			'replyTo' => self::TEST_EMAIL,
			'message' => $longMessage,
		];

		$this->wpAdapter->method( 'sanitizeEmail' )
			->with( self::TEST_EMAIL )
			->willReturn( self::TEST_EMAIL );

		$this->wpAdapter->method( 'wpKsesPost' )
			->with( $longMessage )
			->willReturn( $longMessage );

		$this->wpAdapter->method( 'getBlogInfo' )
			->with( 'name', 'raw' )
			->willReturn( self::TEST_SITE_NAME );

		$this->wpAdapter->method( 'currentTime' )
			->with( 'Y-m-d_H-i-s', false )
			->willReturn( self::TEST_TIMESTAMP );

		$this->wpAdapter->method( 'wpMail' )
			->willReturn( true );

		$this->exporter->method( 'getExportContent' )
			->willReturn( self::TEST_EXPORT_CONTENT );

		$this->latteEngine->method( 'renderToString' )
			->willReturn( self::TEST_EMAIL_BODY );

		$this->wpAdapter->method( '__' )
			->willReturnMap(
				[
					[ self::SUCCESS_MESSAGE, self::TEXT_DOMAIN, self::SUCCESS_MESSAGE ],
				]
			);

		$this->messageManager->expects( $this->once() )
			->method( 'flash_message' )
			->with(
				$this->anything(),
				MessageManager::TYPE_SUCCESS,
				MessageManager::RENDERER_PACKETERY,
				'plugin-options'
			);

		$this->bugReportService->onFormSuccess( $form, $values );
	}

	public function testOnFormValidateWithEmptyMessage(): void {
		$form         = $this->createMock( Form::class );
		$messageField = $this->createMock( BaseControl::class );
		$values       = [
			'replyTo' => 'test@example.com',
			'message' => '',
		];

		$form->method( 'getValues' )
			->willReturn( $values );

		$form->method( 'offsetGet' )
			->with( 'message' )
			->willReturn( $messageField );

		$this->wpAdapter->method( 'wpStripAllTags' )
			->with( '' )
			->willReturn( '' );

		$this->wpAdapter->method( '__' )
			->with( 'Message is required.', self::TEXT_DOMAIN )
			->willReturn( 'Message is required.' );

		$messageField->expects( $this->once() )
			->method( 'addError' )
			->with( 'Message is required.' );

		$this->bugReportService->onFormValidate( $form );
	}

	public function testOnFormValidateWithValidMessage(): void {
		$form         = $this->createMock( Form::class );
		$messageField = $this->createMock( BaseControl::class );
		$values       = [
			'replyTo' => 'test@example.com',
			'message' => 'This is a valid message',
		];

		$form->method( 'getValues' )
			->willReturn( $values );

		$form->method( 'offsetGet' )
			->with( 'message' )
			->willReturn( $messageField );

		$this->wpAdapter->method( 'wpStripAllTags' )
			->with( 'This is a valid message' )
			->willReturn( 'This is a valid message' );

		$messageField->expects( $this->never() )
			->method( 'addError' );

		$this->bugReportService->onFormValidate( $form );
	}

	public function testOnFormSuccessWithWooCommerceSystemStatus(): void {
		$form   = $this->createMock( Form::class );
		$values = [
			'replyTo' => self::TEST_EMAIL,
			'message' => self::TEST_MESSAGE,
		];

		$this->wpAdapter->method( 'sanitizeEmail' )
			->with( self::TEST_EMAIL )
			->willReturn( self::TEST_EMAIL );

		$this->wpAdapter->method( 'wpKsesPost' )
			->with( self::TEST_MESSAGE )
			->willReturn( self::TEST_MESSAGE );

		$this->wpAdapter->method( 'getBlogInfo' )
			->with( 'name', 'raw' )
			->willReturn( self::TEST_SITE_NAME );

		$this->wpAdapter->method( 'currentTime' )
			->with( 'Y-m-d_H-i-s', false )
			->willReturn( self::TEST_TIMESTAMP );

		$this->wpAdapter->method( 'wpMail' )
			->willReturn( true );

		$this->exporter->method( 'getExportContent' )
			->willReturn( self::TEST_EXPORT_CONTENT );

		$this->latteEngine->method( 'renderToString' )
			->willReturn( self::TEST_EMAIL_BODY );

		$this->wpAdapter->method( '__' )
			->willReturnMap(
				[
					[ self::SUCCESS_MESSAGE, self::TEXT_DOMAIN, self::SUCCESS_MESSAGE ],
				]
			);

		$this->wcAdapter->method( 'getSystemStatusReport' )
			->willReturnCallback(
				function () {
					echo 'WooCommerce System Status Report';
				}
			);

		$this->messageManager->expects( $this->once() )
			->method( 'flash_message' )
			->with(
				$this->anything(),
				MessageManager::TYPE_SUCCESS,
				MessageManager::RENDERER_PACKETERY,
				'plugin-options'
			);

		$this->bugReportService->onFormSuccess( $form, $values );
	}
}
