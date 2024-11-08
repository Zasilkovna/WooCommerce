<?php
/**
 * Class CustomsDeclaration
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Core\Entity;

/**
 * Class CustomsDeclaration
 */
class CustomsDeclaration {

	/**
	 * Unique identifier.
	 *
	 * @var ?string
	 */
	private $id;

	/**
	 * Order ID.
	 *
	 * @var string
	 */
	private $orderId;

	/**
	 * EAD.
	 *
	 * @var string
	 */
	private $ead;

	/**
	 * Delivery cost.
	 *
	 * @var float
	 */
	private $deliveryCost;

	/**
	 * Invoice number.
	 *
	 * @var string
	 */
	private $invoiceNumber;

	/**
	 * Invoice issue date.
	 *
	 * @var \DateTimeImmutable
	 */
	private $invoiceIssueDate;

	/**
	 * Tells if invoice file has content.
	 *
	 * @var bool
	 */
	private $hasInvoiceFileContent = false;

	/**
	 * Invoice.
	 *
	 * @var callable|null
	 */
	private $invoiceFile = null;

	/**
	 * Invoice file ID.
	 *
	 * @var string|null
	 */
	private $invoiceFileId = null;

	/**
	 * MRN.
	 *
	 * @var string|null
	 */
	private $mrn = null;

	/**
	 * Tells if EAD file has content.
	 *
	 * @var bool
	 */
	private $hasEadFileContent = false;

	/**
	 * Ead file.
	 *
	 * @var callable|null
	 */
	private $eadFile = null;

	/**
	 * EAD file ID.
	 *
	 * @var string|null
	 */
	private $eadFileId = null;

	/**
	 * Customs declaration item.
	 *
	 * @var CustomsDeclarationItem[]
	 */
	private $items = [];

	/**
	 * Constructor.
	 *
	 * @param string             $orderId          Order ID.
	 * @param string             $ead              Ead.
	 * @param float              $deliveryCost     Delivery cost.
	 * @param string             $invoiceNumber    Invoice number.
	 * @param \DateTimeImmutable $invoiceIssueDate Invoice issue date.
	 */
	public function __construct(
		string $orderId,
		string $ead,
		float $deliveryCost,
		string $invoiceNumber,
		\DateTimeImmutable $invoiceIssueDate
	) {
		$this->orderId          = $orderId;
		$this->ead              = $ead;
		$this->deliveryCost     = $deliveryCost;
		$this->invoiceNumber    = $invoiceNumber;
		$this->invoiceIssueDate = $invoiceIssueDate;
	}

	/**
	 * Gets delivery cost.
	 *
	 * @return float
	 */
	public function getDeliveryCost(): float {
		return $this->deliveryCost;
	}

	/**
	 * Sets delivery cost.
	 *
	 * @param float $deliveryCost Delivery cost.
	 *
	 * @return void
	 */
	public function setDeliveryCost( float $deliveryCost ): void {
		$this->deliveryCost = $deliveryCost;
	}

	/**
	 * Gets EAD.
	 *
	 * @return string
	 */
	public function getEad(): string {
		return $this->ead;
	}

	/**
	 * Set EAD.
	 *
	 * @param string $ead EAD.
	 *
	 * @return void
	 */
	public function setEad( string $ead ): void {
		$this->ead = $ead;
	}

	/**
	 * Gets EAD file.
	 *
	 * @return string|null
	 */
	public function getEadFile(): ?string {
		if ( null === $this->eadFile ) {
			return null;
		}

		return call_user_func( $this->eadFile );
	}

	/**
	 * Sets EAD PDF file content.
	 *
	 * @param callable|null $eadFile    EAD file content.
	 * @param bool          $hasContent Tells if file has content.
	 *
	 * @return void
	 */
	public function setEadFile( ?callable $eadFile, bool $hasContent ): void {
		$this->eadFile           = $eadFile;
		$this->hasEadFileContent = $hasContent;
	}

	/**
	 * Gets EAD file ID.
	 *
	 * @return string|null
	 */
	public function getEadFileId(): ?string {
		return $this->eadFileId;
	}

	/**
	 * Sets EAD file ID.
	 *
	 * @param string|null $eadFileId EAD file ID.
	 *
	 * @return void
	 */
	public function setEadFileId( ?string $eadFileId ): void {
		$this->eadFileId = $eadFileId;
	}

	/**
	 * Gets ID.
	 *
	 * @return string|null
	 */
	public function getId(): ?string {
		return $this->id;
	}

	/**
	 * Sets ID.
	 *
	 * @param string|null $id ID.
	 *
	 * @return void
	 */
	public function setId( ?string $id ): void {
		$this->id = $id;
	}

	/**
	 * Gets invoice.
	 *
	 * @return string|null
	 */
	public function getInvoiceFile(): ?string {
		if ( null === $this->invoiceFile ) {
			return null;
		}

		return call_user_func( $this->invoiceFile );
	}

	/**
	 * Sets invoice.
	 *
	 * @param callable|null $invoice    Invoice.
	 * @param bool          $hasContent Tells if file has content.
	 *
	 * @return void
	 */
	public function setInvoiceFile( ?callable $invoice, bool $hasContent ): void {
		$this->invoiceFile           = $invoice;
		$this->hasInvoiceFileContent = $hasContent;
	}

	/**
	 * Gets invoice file ID.
	 *
	 * @return string|null
	 */
	public function getInvoiceFileId(): ?string {
		return $this->invoiceFileId;
	}

	/**
	 * Sets invoice file ID.
	 *
	 * @param string|null $invoiceFileId Invoice file ID.
	 *
	 * @return void
	 */
	public function setInvoiceFileId( ?string $invoiceFileId ): void {
		$this->invoiceFileId = $invoiceFileId;
	}

	/**
	 * Gets invoice issue date.
	 *
	 * @return \DateTimeImmutable
	 */
	public function getInvoiceIssueDate(): \DateTimeImmutable {
		return $this->invoiceIssueDate;
	}

	/**
	 * Sets invoice issue date.
	 *
	 * @param \DateTimeImmutable $invoiceIssueDate Invoice issue date.
	 *
	 * @return void
	 */
	public function setInvoiceIssueDate( \DateTimeImmutable $invoiceIssueDate ): void {
		$this->invoiceIssueDate = $invoiceIssueDate;
	}

	/**
	 * Gets invoice number.
	 *
	 * @return string
	 */
	public function getInvoiceNumber(): string {
		return $this->invoiceNumber;
	}

	/**
	 * Sets invoice number.
	 *
	 * @param string $invoiceNumber Invoice number.
	 *
	 * @return void
	 */
	public function setInvoiceNumber( string $invoiceNumber ): void {
		$this->invoiceNumber = $invoiceNumber;
	}

	/**
	 * Gets items.
	 *
	 * @return CustomsDeclarationItem[]
	 */
	public function getItems(): array {
		return $this->items;
	}

	/**
	 * Sets items.
	 *
	 * @param CustomsDeclarationItem[] $items Items.
	 *
	 * @return void
	 */
	public function setItems( array $items ): void {
		$this->items = $items;
	}

	/**
	 * Gets MRN.
	 *
	 * @return string|null
	 */
	public function getMrn(): ?string {
		return $this->mrn;
	}

	/**
	 * Sets MRN.
	 *
	 * @param string|null $mrn MRN.
	 *
	 * @return void
	 */
	public function setMrn( ?string $mrn ): void {
		$this->mrn = $mrn;
	}

	/**
	 * Get order ID.
	 *
	 * @return string|null
	 */
	public function getOrderId(): ?string {
		return $this->orderId;
	}

	/**
	 * Tells if EAD file is present.
	 *
	 * @return bool
	 */
	public function hasEadFileContent(): bool {
		return $this->hasEadFileContent;
	}

	/**
	 * Tells if invoice file is set.
	 *
	 * @return bool
	 */
	public function hasInvoiceFileContent(): bool {
		return $this->hasInvoiceFileContent;
	}
}
