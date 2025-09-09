<?php

declare( strict_types=1 );

namespace Packetery\Module\Log;

use DateTimeImmutable;
use DateTimeZone;

class LogSizeLimiter {
	/** @var int */
	private $logLimitSizeBytes;

	/** @var int */
	private $logLimitDays;

	public function __construct(
		int $logLimitSizeBytes,
		int $logLimitDays
	) {
		$this->logLimitSizeBytes = $logLimitSizeBytes;
		$this->logLimitDays      = $logLimitDays;
	}

	public function getLimitedFilePartAsString( string $logPath ): ?string {
		if ( $this->logLimitSizeBytes <= 0 || $this->logLimitDays <= 0 || ! is_file( $logPath ) ) {
			return null;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$handle = fopen( $logPath, 'rb' );
		if ( $handle === false ) {
			return null;
		}

		$state = new LogSizeLimiterState( [], 0, [], null );

		// phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
		while ( $line = fgets( $handle ) ) {
			$matches = [];
			if ( preg_match( '/^\[(.+?)]/', $line, $matches ) === 1 ) {
				$timestamp = null;
				if ( $this->isStrToTimeTokenValid( $matches[1] ) ) {
					$maybeTimestamp = strtotime( $matches[1] );
					$timestamp      = $maybeTimestamp !== false ? $maybeTimestamp : null;
				}
				if ( $timestamp !== null ) {
					$this->flushCurrentRecord( $state );
					$state->setCurrentRecordLines( [ $line ] );
					$state->setCurrentRecordTime( $timestamp );

					continue;
				}
			}
			if ( $state->getCurrentRecordTime() !== null ) {
				$state->addCurrentRecordLine( $line );
			}
		}

		$this->flushCurrentRecord( $state );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $handle );

		if ( $state->getRecordQueue() === [] ) {
			return null;
		}

		return implode( '', $state->getRecordQueue() );
	}

	/**
	 * Flushes the currently built record into the queue if it passes age and size checks.
	 */
	private function flushCurrentRecord( LogSizeLimiterState $state ): void {
		if ( $state->getCurrentRecordTime() === null || $state->getCurrentRecordLines() === [] ) {
			$state->emptyCurrentRecordLines();

			return;
		}
		if ( $state->getCurrentRecordTime() < $this->getCutoffTimestamp() ) {
			$state->emptyCurrentRecordLines();

			return;
		}
		$recordContent = implode( '', $state->getCurrentRecordLines() );
		$recordLength  = strlen( $recordContent );
		if ( $recordLength > $this->logLimitSizeBytes ) {
			$state->emptyCurrentRecordLines();

			return;
		}
		$state->addRecordToQueue( $recordContent );
		$state->addToTotalSize( $recordLength );
		while ( $state->getTotalSize() > $this->logLimitSizeBytes && $state->getRecordQueue() !== [] ) {
			$removed = $state->shiftRecordQueue();
			$state->subtractFromTotalSize( is_string( $removed ) ? strlen( $removed ) : 0 );
		}
		$state->emptyCurrentRecordLines();
	}

	/**
	 * Validates that the token to be passed to strtotime contains only allowed characters.
	 * Allows digits, letters, plus, minus, colon, space, slash, dot, comma, underscore and parentheses.
	 */
	private function isStrToTimeTokenValid( string $possibleTimeString ): bool {
		$possibleTimeString = trim( $possibleTimeString );
		if ( $possibleTimeString === '' ) {
			return false;
		}

		return preg_match( '/^[0-9A-Za-z\-:+\s\/.,_()]+$/', $possibleTimeString ) === 1;
	}

	private function getCutoffTimestamp(): int {
		$now = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );

		return $now->getTimestamp() - ( $this->logLimitDays * DAY_IN_SECONDS );
	}

	public function isFileFreshEnough( string $filePath ): bool {
		if ( $this->logLimitDays <= 0 || ! is_file( $filePath ) ) {
			return false;
		}

		$fileModificationTime = filemtime( $filePath );
		if ( $fileModificationTime === false ) {
			return false;
		}

		return $fileModificationTime >= $this->getCutoffTimestamp();
	}
}
