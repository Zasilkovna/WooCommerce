<?php

declare( strict_types=1 );

namespace Packetery\Module\Email;

use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Log\LogSizeLimiter;
use Packetery\Module\Options\Exporter;
use Packetery\Tracy\Debugger;
use ZipArchive;

class BugReportAttachment {

	/** @var WpAdapter */
	private $wpAdapter;

	/** @var WcAdapter */
	private $wcAdapter;

	/** @var Exporter */
	private $exporter;

	/** @var LogSizeLimiter */
	private $logSizeLimiter;

	public function __construct(
		WpAdapter $wpAdapter,
		WcAdapter $wcAdapter,
		Exporter $exporter,
		LogSizeLimiter $logSizeLimiter
	) {
		$this->wpAdapter      = $wpAdapter;
		$this->wcAdapter      = $wcAdapter;
		$this->exporter       = $exporter;
		$this->logSizeLimiter = $logSizeLimiter;
	}

	/**
	 * @return string[]
	 */
	public function createAttachments(): array {
		$attachments = [];
		if ( ! class_exists( 'ZipArchive' ) ) {
			return $attachments;
		}

		$timestamp   = $this->wpAdapter->currentTime( 'Y-m-d_H-i-s', false );
		$zipFilename = "logs-{$timestamp}.zip";

		$zip     = new ZipArchive();
		$zipPath = sys_get_temp_dir() . '/' . $zipFilename;

		if ( $zip->open( $zipPath, ZipArchive::CREATE ) === true ) {
			$zip->addFromString( 'packeta-settings.txt', $this->exporter->getExportContent() );
			$this->addWooCommerceSystemStatusToZip( $zip );
			$this->addPacketaWcLogsToZip( $zip );
			$this->addWpDebugLogToZip( $zip );
			$this->addTracyLogsToZip( $zip );

			$zip->close();
			if ( is_file( $zipPath ) ) {
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

	private function addPacketaWcLogsToZip( ZipArchive $zip ): void {
		$logDirectory = $this->wcAdapter->loggingUtilGetLogDirectory( false );
		if ( $logDirectory === null || ! is_dir( $logDirectory ) ) {
			return;
		}
		$fileNames = scandir( $logDirectory );
		if ( ! is_array( $fileNames ) ) {
			return;
		}
		foreach ( $fileNames as $fileName ) {
			$filePath = $logDirectory . '/' . $fileName;
			if ( is_file( $filePath ) && strpos( $fileName, 'packeta-' ) === 0 ) {
				$zip->addFile( $filePath, $fileName );
			}
		}
	}

	private function addWpDebugLogToZip( ZipArchive $zip ): void {
		$debugLogPath = null;
		// Snippet from function `wp_debug_mode`.
		if ( in_array( strtolower( (string) WP_DEBUG_LOG ), array( 'true', '1' ), true ) ) {
			$debugLogPath = WP_CONTENT_DIR . '/debug.log';
		} elseif ( is_string( WP_DEBUG_LOG ) ) {
			$debugLogPath = WP_DEBUG_LOG;
		}
		if ( is_string( $debugLogPath ) ) {
			$this->addLimitedLogContentsToZip( $debugLogPath, $zip, 'debug.log' );
		}
	}

	private function addTracyLogsToZip( ZipArchive $zip ): void {
		$logDirectory = Debugger::$logDirectory;
		if ( $logDirectory === null ) {
			return;
		}

		foreach ( [ 'packeta.log', 'exception.log', 'error.log' ] as $filename ) {
			$this->addLimitedLogContentsToZip( $logDirectory . '/' . $filename, $zip, $filename );
		}

		$fileNames = scandir( $logDirectory );
		if ( ! is_array( $fileNames ) ) {
			return;
		}
		foreach ( $fileNames as $fileName ) {
			$filePath = $logDirectory . '/' . $fileName;
			if ( is_file( $filePath ) && strpos( $fileName, 'exception-' ) === 0 && $this->logSizeLimiter->isFileFreshEnough( $filePath ) ) {
				$zip->addFile( $filePath, $fileName );
			}
		}
	}

	private function addLimitedLogContentsToZip( string $logPath, ZipArchive $zip, string $fileName ): void {
		if ( is_file( $logPath ) ) {
			$limited = $this->logSizeLimiter->getLimitedFilePartAsString( $logPath );
			if ( is_string( $limited ) && $limited !== '' ) {
				$zip->addFromString( $fileName, $limited );
			} else {
				$zip->addFile( $logPath, $fileName );
			}
		}
	}
}
