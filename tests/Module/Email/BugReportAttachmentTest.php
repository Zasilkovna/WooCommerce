<?php

declare( strict_types=1 );

namespace Email;

use Packetery\Module\Email\BugReportAttachment;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Log\LogSizeLimiter;
use Packetery\Module\Options\Exporter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ZipArchive;

class BugReportAttachmentTest extends TestCase {
	private WpAdapter&MockObject $wpAdapter;
	private WcAdapter&MockObject $wcAdapter;
	private Exporter&MockObject $exporter;
	private LogSizeLimiter&MockObject $logSizeLimiter;

	private const TEST_EMAIL          = 'test@example.com';
	private const TEST_SITE_NAME      = 'Test Site';
	private const TEST_MESSAGE        = 'Test bug report message';
	private const TEST_TIMESTAMP      = '2024-01-01_12-00-00';
	private const TEST_EXPORT_CONTENT = 'exported settings content';

	private function createBugReportAttachment(): BugReportAttachment {
		$this->wpAdapter = $this->createMock( WpAdapter::class );
		$this->wcAdapter = $this->createMock( WcAdapter::class );
		$this->wcAdapter->method( 'loggingUtilGetLogDirectory' )
						->willReturn( '' );
		$this->exporter       = $this->createMock( Exporter::class );
		$this->logSizeLimiter = $this->createMock( LogSizeLimiter::class );

		return new BugReportAttachment(
			$this->wpAdapter,
			$this->wcAdapter,
			$this->exporter,
			$this->logSizeLimiter,
		);
	}

	public function testCreateAttachmentsMethod(): void {
		$bugReportAttachment = $this->createBugReportAttachment();
		$result              = $bugReportAttachment->createAttachments();
		$this->assertIsArray( $result );
	}

	public function testCreateAttachmentsWithZipArchiveAndWooCommerce(): void {
		$bugReportAttachment = $this->createBugReportAttachment();

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

		$result = $bugReportAttachment->createAttachments();
		$this->assertIsArray( $result );
		if ( class_exists( 'ZipArchive' ) ) {
			$this->assertNotEmpty( $result );
		}
	}

	public function testAddWooCommerceSystemStatusToZip(): void {
		$bugReportAttachment = $this->createBugReportAttachment();

		$this->wcAdapter->method( 'adminStatusStatusReport' )
			->willReturnCallback(
				function () {
					echo 'WooCommerce System Status Content';
				}
			);

		$reflection = new ReflectionClass( $bugReportAttachment );
		$method     = $reflection->getMethod( 'addWooCommerceSystemStatusToZip' );
		$method->setAccessible( true );

		$zipMock = $this->createMock( ZipArchive::class );
		$zipMock->expects( $this->once() )
			->method( 'addFromString' )
			->with( 'woocommerce-system-status.html', 'WooCommerce System Status Content' );

		$method->invoke( $bugReportAttachment, $zipMock );
	}

	public function testCreateAttachmentsWithRealZipArchive(): void {
		$bugReportAttachment = $this->createBugReportAttachment();

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

		$result = $bugReportAttachment->createAttachments();
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
