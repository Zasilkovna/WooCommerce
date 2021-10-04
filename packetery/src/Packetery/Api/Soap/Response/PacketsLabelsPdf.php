<?php
/**
 * Class PacketsLabelsPdf.
 *
 * @package Packetery\Api\Soap\Response
 */

namespace Packetery\Api\Soap\Response;

/**
 * Class PacketsLabelsPdf.
 *
 * @package Packetery\Api\Soap\Response
 */
class PacketsLabelsPdf {

	/**
	 * Fault string.
	 *
	 * @var string
	 */
	private $faultString;

	/**
	 * Pdf contents.
	 *
	 * @var string
	 */
	private $pdfContents;

	/**
	 * Sets pdf contents.
	 *
	 * @param string $pdfContents Pdf contents.
	 */
	public function setPdfContents( string $pdfContents ): void {
		$this->pdfContents = $pdfContents;
	}

	/**
	 * Sets fault string.
	 *
	 * @param string $faultString Fault string.
	 */
	public function setFaultString( string $faultString ): void {
		$this->faultString = $faultString;
	}

	/**
	 * Gets pdf contents.
	 *
	 * @return string
	 */
	public function getPdfContents(): string {
		return $this->pdfContents;
	}

	/**
	 * Gets fault string.
	 *
	 * @return string|null
	 */
	public function getFaultString(): ?string {
		return $this->faultString;
	}

}
