<?php
/**
 * Class PacketsLabelsPdf
 *
 * @package Packetery\Api\Soap\Request
 */

declare( strict_types=1 );

namespace Packetery\Core\Api\Soap\Request;

/**
 * Class PacketsLabelsPdf
 *
 * @package Packetery\Api\Soap\Request
 */
class PacketsLabelsPdf {

	/**
	 * Packet ids.
	 *
	 * @var array<string, string>
	 */
	private $packetIds;

	/**
	 * Label format.
	 *
	 * @var string
	 */
	private $labelFormat;

	/**
	 * Offset.
	 *
	 * @var int
	 */
	private $offset;

	/**
	 * PacketsLabelsPdf constructor.
	 *
	 * @param array<string, string> $packetIds   Packet ids.
	 * @param string                $labelFormat Label format.
	 * @param int                   $offset      Offset.
	 */
	public function __construct( array $packetIds, string $labelFormat, int $offset ) {
		$this->packetIds   = $packetIds;
		$this->labelFormat = $labelFormat;
		$this->offset      = $offset;
	}

	/**
	 * Gets packet ids.
	 *
	 * @return array<string, string>
	 */
	public function getPacketIds(): array {
		return $this->packetIds;
	}

	/**
	 * Gets label format.
	 *
	 * @return string
	 */
	public function getFormat(): string {
		return $this->labelFormat;
	}

	/**
	 * Gets offset.
	 *
	 * @return int
	 */
	public function getOffset(): int {
		return $this->offset;
	}
}
