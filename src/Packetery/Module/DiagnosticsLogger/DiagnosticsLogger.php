<?php

namespace Packetery\Module\DiagnosticsLogger;

use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\MessageManager;
use Packetery\Module\Options\OptionNames;
use Packetery\Module\Options\Page;
use Packetery\Nette\Http;
use Packetery\Tracy\Debugger;
use Packetery\Tracy\ILogger;

class DiagnosticsLogger {

	public const ACTION_DELETE_PACKETA_LOG = 'delete_packeta_log';

	/**
	 * @var ILogger
	 */
	private $logger;

	/**
	 * @var Http\Request
	 */
	private $httpRequest;

	/**
	 * @var MessageManager
	 */
	private $messageManager;

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	public function __construct( Http\Request $httpRequest, MessageManager $messageManager, WpAdapter $wpAdapter ) {
		$this->logger         = Debugger::getLogger();
		$this->httpRequest    = $httpRequest;
		$this->messageManager = $messageManager;
		$this->wpAdapter      = $wpAdapter;
	}

	/**
	 * @param string               $logMessage
	 * @param array<string, mixed> $arguments
	 * @return void
	 */
	public function log( string $logMessage, array $arguments ): void {
		if ( $this->isLoggingEnabled() === false ) {
			return;
		}

		$logMessage .= '  @  (PID: ' . getmypid() . ')';
		$logMessage .= '  @  Arguments: ' . wp_json_encode( $arguments, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );

		$this->logger->log( $logMessage, 'packeta' );
	}

	public function deletePacketaLog(): void {
		if (
			$this->httpRequest->getQuery( 'page' ) !== Page::SLUG ||
			$this->httpRequest->getQuery( 'action' ) !== self::ACTION_DELETE_PACKETA_LOG
		) {
			return;
		}

		$file = $this->getPacketaLogPath();

		if ( is_file( $file ) === false ) {
			return;
		}

		$isDeleteSuccess = wp_delete_file( $file );

		if ( $isDeleteSuccess === true ) {
			$this->messageManager->flash_message(
				$this->wpAdapter->__( 'Log file was deleted.' ),
				MessageManager::TYPE_SUCCESS,
				MessageManager::RENDERER_PACKETERY,
				'plugin-options'
			);
		} else {
			$this->messageManager->flash_message(
				$this->wpAdapter->__( 'Log file could not be deleted.' ),
				MessageManager::TYPE_ERROR,
				MessageManager::RENDERER_PACKETERY,
				'plugin-options'
			);
		}
	}

	private function isLoggingEnabled(): bool {
		return (bool) $this->wpAdapter->getOption( OptionNames::PACKETERY_DIAGNOSTICS_LOGGING_ENABLED ) === true;
	}

	public function getPacketaLogPath(): string {
		$logDir = Debugger::$logDirectory;

		return $logDir . '/packeta.log';
	}
}
