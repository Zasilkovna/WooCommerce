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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BugReportServiceTest extends TestCase {
	private WpAdapter&MockObject $wpAdapter;
	private WcAdapter&MockObject $wcAdapter;
	private Exporter&MockObject $exporter;
	private Engine&MockObject $latteEngine;
	private MessageManager&MockObject $messageManager;

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

	private function createBugReportService(): BugReportService {
		$this->wpAdapter      = $this->createMock( WpAdapter::class );
		$this->wcAdapter      = $this->createMock( WcAdapter::class );
		$this->exporter       = $this->createMock( Exporter::class );
		$this->latteEngine    = $this->createMock( Engine::class );
		$this->messageManager = $this->createMock( MessageManager::class );

		return new BugReportService(
			$this->wpAdapter,
			$this->wcAdapter,
			$this->exporter,
			'support@packeta.com',
			$this->latteEngine,
			$this->messageManager
		);
	}

	public function testCreateForm(): void {
		$service = $this->createBugReportService();

		$this->wpAdapter->method( '__' )
			->willReturnCallback(
				function ( $text ) {
					return $text;
				}
			);

		$this->wpAdapter->method( 'getOption' )
			->with( 'admin_email' )
			->willReturn( self::ADMIN_EMAIL );

		$form = $service->createForm();

		$this->assertNotNull( $form->getComponent( 'replyTo' ) );
		$this->assertNotNull( $form->getComponent( 'message' ) );
		$this->assertNotNull( $form->getComponent( 'submit' ) );
	}

	public function testOnFormSuccessWithValidData(): void {
		$service = $this->createBugReportService();

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

		$service->onFormSuccess( $formMock, $values );
	}

	public function testOnFormSuccessWithFailedEmail(): void {
		$service = $this->createBugReportService();

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

		$service->onFormSuccess( $formMock, $values );
	}

	public function testOnFormError(): void {
		$service = $this->createBugReportService();

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

		$service->onFormError( $formMock );
	}

	public function testOnFormSuccessWithSpecialCharacters(): void {
		$service = $this->createBugReportService();

		$formMock       = $this->createMock( Form::class );
		$specialEmail   = 'test+tag@example.com';
		$specialMessage = 'Test message with <script>alert("xss")</script> and special chars: áčďéěíňóřšťúůýž';
		$values         = [
			'replyTo' => $specialEmail,
			'message' => $specialMessage,
		];

		$this->wpAdapter->method( 'sanitizeEmail' )
			->with( $specialEmail )
			->willReturn( $specialEmail );

		$this->wpAdapter->method( 'wpKsesPost' )
			->with( $specialMessage )
			->willReturn( $specialMessage );

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
			->willReturnCallback(
				function ( $text ) {
					return $text;
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

		$service->onFormSuccess( $formMock, $values );
	}

	public function testCreateFormWithCustomAdminEmail(): void {
		$service = $this->createBugReportService();

		$this->wpAdapter->method( '__' )
			->willReturnCallback(
				function ( $text ) {
					return $text;
				}
			);

		$this->wpAdapter->method( 'getOption' )
			->with( 'admin_email' )
			->willReturn( self::CUSTOM_EMAIL );

		$form = $service->createForm();

		$this->assertNotNull( $form->getComponent( 'replyTo' ) );
		$this->assertNotNull( $form->getComponent( 'message' ) );
		$this->assertNotNull( $form->getComponent( 'submit' ) );
	}

	public function testOnFormValidateWithEmptyMessage(): void {
		$service = $this->createBugReportService();

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

		$service->onFormValidate( $formMock );
	}

	public function testOnFormValidateWithValidMessage(): void {
		$service = $this->createBugReportService();

		$formMock         = $this->createMock( Form::class );
		$messageFieldMock = $this->createMock( BaseControl::class );
		$values           = [
			'replyTo' => self::TEST_EMAIL,
			'message' => 'This is a valid message',
		];

		$formMock->method( 'getValues' )
			->willReturn( $values );

		$formMock->method( 'offsetGet' )
			->with( 'message' )
			->willReturn( $messageFieldMock );

		$this->wpAdapter->method( 'wpStripAllTags' )
			->with( 'This is a valid message' )
			->willReturn( 'This is a valid message' );

		$messageFieldMock->expects( $this->never() )
			->method( 'addError' );

		$service->onFormValidate( $formMock );
	}

	public function testOnFormSuccessWithWooCommerceSystemStatus(): void {
		$service = $this->createBugReportService();

		$formMock = $this->createMock( Form::class );
		$values   = [
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
			->willReturnCallback(
				function ( $text ) {
					return $text;
				}
			);

		$this->wcAdapter->method( 'adminStatusStatusReport' )
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

		$service->onFormSuccess( $formMock, $values );
	}

	public function testOnFormSuccessWithoutZipArchive(): void {
		$service = $this->createBugReportService();

		$formMock = $this->createMock( Form::class );
		$values   = [
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
			->willReturnCallback(
				function ( $text ) {
					return $text;
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

		$service->onFormSuccess( $formMock, $values );
	}

	public function testOnFormSuccessWithoutWooCommerceSystemStatus(): void {
		$service = $this->createBugReportService();

		$formMock = $this->createMock( Form::class );
		$values   = [
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
			->willReturnCallback(
				function ( $text ) {
					return $text;
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

		$service->onFormSuccess( $formMock, $values );
	}

	public function testCreateAttachmentsMethod(): void {
		$service = $this->createBugReportService();

		// Test the createAttachments method using reflection
		$reflection = new \ReflectionClass( $service );
		$method     = $reflection->getMethod( 'createAttachments' );
		$method->setAccessible( true );

		$result = $method->invoke( $service );
		$this->assertIsArray( $result );
	}

	public function testCreateAttachmentsWithZipArchiveAndWooCommerce(): void {
		$service = $this->createBugReportService();

		$this->wpAdapter->method( 'currentTime' )
			->with( 'Y-m-d_H-i-s', false )
			->willReturn( self::TEST_TIMESTAMP );

		$this->exporter->method( 'getExportContent' )
			->willReturn( self::TEST_EXPORT_CONTENT );

		$this->wcAdapter->method( 'adminStatusStatusReport' )
			->willReturnCallback(
				function () {
					echo 'WooCommerce System Status Content';
				}
			);

		$reflection = new \ReflectionClass( $service );
		$method     = $reflection->getMethod( 'createAttachments' );
		$method->setAccessible( true );

		$result = $method->invoke( $service );
		$this->assertIsArray( $result );
		if ( class_exists( 'ZipArchive' ) ) {
			$this->assertNotEmpty( $result );
		}
	}

	public function testAddWooCommerceSystemStatusToZip(): void {
		$service = $this->createBugReportService();

		$this->wcAdapter->method( 'adminStatusStatusReport' )
			->willReturnCallback(
				function () {
					echo 'WooCommerce System Status Content';
				}
			);

		$reflection = new \ReflectionClass( $service );
		$method     = $reflection->getMethod( 'addWooCommerceSystemStatusToZip' );
		$method->setAccessible( true );

		$zipMock = $this->createMock( \ZipArchive::class );
		$zipMock->expects( $this->once() )
			->method( 'addFromString' )
			->with( 'woocommerce-system-status.html', 'WooCommerce System Status Content' );

		$method->invoke( $service, $zipMock );
	}

	public function testOnFormSuccessWithFullZipCreation(): void {
		$service = $this->createBugReportService();

		$formMock = $this->createMock( Form::class );
		$values   = [
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
			->willReturnCallback(
				function ( $text ) {
					return $text;
				}
			);

		$this->wcAdapter->method( 'adminStatusStatusReport' )
			->willReturnCallback(
				function () {
					echo 'WooCommerce System Status Content';
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

		$service->onFormSuccess( $formMock, $values );
	}

	public function testCreateAttachmentsWithRealZipArchive(): void {
		$service = $this->createBugReportService();

		$this->wpAdapter->method( 'currentTime' )
			->with( 'Y-m-d_H-i-s', false )
			->willReturn( self::TEST_TIMESTAMP );

		$this->exporter->method( 'getExportContent' )
			->willReturn( self::TEST_EXPORT_CONTENT );

		$this->wcAdapter->method( 'adminStatusStatusReport' )
			->willReturnCallback(
				function () {
					echo 'WooCommerce System Status Content';
				}
			);

		$reflection = new \ReflectionClass( $service );
		$method     = $reflection->getMethod( 'createAttachments' );
		$method->setAccessible( true );

		$result = $method->invoke( $service );
		$this->assertIsArray( $result );

		if ( class_exists( 'ZipArchive' ) ) {
			$this->assertNotEmpty( $result );
			$this->assertCount( 1, $result );
			$this->assertStringContainsString( 'logs-', $result[0] );
			$this->assertStringContainsString( '.zip', $result[0] );

			if ( file_exists( $result[0] ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
				unlink( $result[0] );
			}
		}
	}
}
