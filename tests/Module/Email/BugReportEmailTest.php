<?php

declare( strict_types=1 );

namespace Tests\Module\Email;

use Packetery\Latte\Engine;
use Packetery\Module\Email\BugReportAttachment;
use Packetery\Module\Email\BugReportEmail;
use Packetery\Module\Framework\WpAdapter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BugReportEmailTest extends TestCase {
	private WpAdapter&MockObject $wpAdapter;
	private Engine&MockObject $latteEngine;
	private BugReportAttachment&MockObject $bugReportAttachment;

	private const TEST_EMAIL          = 'test@example.com';
	private const TEST_SITE_NAME      = 'Test Site';
	private const TEST_MESSAGE        = 'Test bug report message';
	private const TEST_TIMESTAMP      = '2024-01-01_12-00-00';
	private const TEST_EXPORT_CONTENT = 'exported settings content';

	private function createBugReportEmail(): BugReportEmail {
		$this->wpAdapter           = $this->createMock( WpAdapter::class );
		$this->latteEngine         = $this->createMock( Engine::class );
		$this->bugReportAttachment = $this->createMock( BugReportAttachment::class );

		return new BugReportEmail(
			$this->wpAdapter,
			'support@packeta.com',
			$this->latteEngine,
			$this->bugReportAttachment,
		);
	}

	public function testSuccessWithSpecialCharacters(): void {
		$bugReportEmail = $this->createBugReportEmail();

		$specialEmail   = 'test+tag@example.com';
		$specialMessage = 'Test message with <script>alert("xss")</script> and special chars: áčďéěíňóřšťúůýž';

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

		$this->wpAdapter->method( '__' )
			->willReturnCallback(
				function ( $text ) {
					return $text;
				}
			);

		$this->assertTrue( $bugReportEmail->sendBugReport( $specialEmail, $specialMessage, false ) );
	}

	public function testSuccessWithWooCommerceSystemStatus(): void {
		$bugReportEmail = $this->createBugReportEmail();

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

		$this->wpAdapter->method( '__' )
			->willReturnCallback(
				function ( $text ) {
					return $text;
				}
			);

		$this->assertTrue( $bugReportEmail->sendBugReport( self::TEST_EMAIL, self::TEST_MESSAGE, false ) );
	}

	public function testSuccessWithoutZipArchive(): void {
		$bugReportEmail = $this->createBugReportEmail();

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

		$this->wpAdapter->method( '__' )
			->willReturnCallback(
				function ( $text ) {
					return $text;
				}
			);

		$this->assertTrue( $bugReportEmail->sendBugReport( self::TEST_EMAIL, self::TEST_MESSAGE, false ) );
	}

	public function testSuccessWithoutWooCommerceSystemStatus(): void {
		$bugReportEmail = $this->createBugReportEmail();

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

		$this->wpAdapter->method( '__' )
			->willReturnCallback(
				function ( $text ) {
					return $text;
				}
			);

		$this->assertTrue( $bugReportEmail->sendBugReport( self::TEST_EMAIL, self::TEST_MESSAGE, false ) );
	}

	public function testSuccessWithFullZipCreation(): void {
		$bugReportEmail = $this->createBugReportEmail();

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

		$this->wpAdapter->method( '__' )
			->willReturnCallback(
				function ( $text ) {
					return $text;
				}
			);

		$this->assertTrue( $bugReportEmail->sendBugReport( self::TEST_EMAIL, self::TEST_MESSAGE, false ) );
	}
}
