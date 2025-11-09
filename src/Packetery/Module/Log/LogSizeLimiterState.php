<?php

declare( strict_types=1 );

namespace Packetery\Module\Log;

class LogSizeLimiterState {

	/** @var array<int, string> */
	private $recordQueue;

	/** @var int */
	private $totalSize;

	/** @var array<int, string> */
	private $currentRecordLines;

	/** @var int|null */
	private $currentRecordTime;

	/**
	 * @param array<int, string> $recordQueue
	 * @param int                $totalSize
	 * @param array<int, string> $currentRecordLines
	 * @param int|null           $currentRecordTime
	 */
	public function __construct(
		array $recordQueue,
		int $totalSize,
		array $currentRecordLines,
		?int $currentRecordTime
	) {
		$this->recordQueue        = $recordQueue;
		$this->totalSize          = $totalSize;
		$this->currentRecordLines = $currentRecordLines;
		$this->currentRecordTime  = $currentRecordTime;
	}

	/**
	 * @return array<int, string>
	 */
	public function getRecordQueue(): array {
		return $this->recordQueue;
	}

	public function shiftRecordQueue(): ?string {
		return array_shift( $this->recordQueue );
	}

	public function addRecordToQueue( string $recordContent ): void {
		$this->recordQueue[] = $recordContent;
	}

	public function getTotalSize(): int {
		return $this->totalSize;
	}

	public function addToTotalSize( int $size ): void {
		$this->totalSize += $size;
	}

	public function subtractFromTotalSize( int $size ): void {
		$this->totalSize -= $size;
	}

	/**
	 * @return array<int, string>
	 */
	public function getCurrentRecordLines(): array {
		return $this->currentRecordLines;
	}

	/**
	 * @param array<int, string> $currentRecordLines
	 */
	public function setCurrentRecordLines( array $currentRecordLines ): void {
		$this->currentRecordLines = $currentRecordLines;
	}

	public function addCurrentRecordLine( string $line ): void {
		$this->currentRecordLines[] = $line;
	}

	public function emptyCurrentRecordLines(): void {
		$this->currentRecordLines = [];
	}

	public function getCurrentRecordTime(): ?int {
		return $this->currentRecordTime;
	}

	public function setCurrentRecordTime( ?int $currentRecordTime ): void {
		$this->currentRecordTime = $currentRecordTime;
	}
}
