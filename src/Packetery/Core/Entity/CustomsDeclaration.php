<?php
/**
 * Class CustomsDeclaration
 *
 * @package Packetery
 */

declare(strict_types=1);

namespace Packetery\Core\Entity;

/**
 * Class CustomsDeclaration
 */
class CustomsDeclaration {

	/**
	 * Unique identifier.
	 *
	 * @var string
	 */
	private $id;

	/**
	 * Order.
	 *
	 * @var Order
	 */
	private $order;

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
	 * Invoice.
	 *
	 * @var string|null
	 */
	private $invoiceFile = null;

	/**
	 * MRN.
	 *
	 * @var string|null
	 */
	private $mrn = null;

	/**
	 * Ead file.
	 *
	 * @var string|null
	 */
	private $eadFile = null;

	/**
	 * Constructor.
	 *
	 * @param string|null                  $id ID.
	 * @param \Packetery\Core\Entity\Order $order Order.
	 * @param string                       $ead Ead.
	 * @param float                        $deliveryCost Delivery cost.
	 * @param string                       $invoiceNumber Invoice number.
	 * @param \DateTimeImmutable           $invoiceIssueDate Invoice issue date.
	 */
	public function __construct(
		?string $id,
		Order $order,
		string $ead,
		float $deliveryCost,
		string $invoiceNumber,
		\DateTimeImmutable $invoiceIssueDate
	) {
		$this->id               = $id;
		$this->order            = $order;
		$this->orderId          = $order->getNumber();
		$this->ead              = $ead;
		$this->deliveryCost     = $deliveryCost;
		$this->invoiceNumber    = $invoiceNumber;
		$this->invoiceIssueDate = $invoiceIssueDate;
	}

	/**
	 * Sets ID.
	 *
	 * @param string $id ID.
	 * @return void
	 */
	public function setId( string $id ): void {
		$this->id = $id;
	}

	/**
	 * Gets invoice.
	 *
	 * @return string|null
	 */
	public function getInvoiceFile(): ?string {
		return $this->invoiceFile;
	}

	/**
	 * Sets invoice.
	 *
	 * @param string|null $invoice Invoice.
	 * @return void
	 */
	public function setInvoiceFile( ?string $invoice ): void {
		$this->invoiceFile = $invoice;
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
	 * @return void
	 */
	public function setMrn( ?string $mrn ): void {
		$this->mrn = $mrn;
	}

	/**
	 * Gets EAD file.
	 *
	 * @return string|null
	 */
	public function getEadFile(): ?string {
		return $this->eadFile;
	}

	/**
	 * Sets EAD PDF file content.
	 *
	 * @param string|null $eadFile EAD file content.
	 * @return void
	 */
	public function setEadFile( ?string $eadFile ): void {
		$this->eadFile = $eadFile;
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
	 * Gets order.
	 *
	 * @return \Packetery\Core\Entity\Order
	 */
	public function getOrder(): Order {
		return $this->order;
	}

	/**
	 * Gets order ID.
	 *
	 * @return string
	 */
	public function getOrderId(): string {
		return $this->orderId;
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
	 * Gets delivery cost.
	 *
	 * @return float
	 */
	public function getDeliveryCost(): float {
		return $this->deliveryCost;
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
	 * Gets invoice issue date.
	 *
	 * @return \DateTimeImmutable
	 */
	public function getInvoiceIssueDate(): \DateTimeImmutable {
		return $this->invoiceIssueDate;
	}

	/**
	 * Set EAD.
	 *
	 * @param string $ead EAD.
	 * @return void
	 */
	public function setEad( string $ead ): void {
		$this->ead = $ead;
	}

	/**
	 * Sets delivery cost.
	 *
	 * @param float $deliveryCost Delivery cost.
	 * @return void
	 */
	public function setDeliveryCost( float $deliveryCost ): void {
		$this->deliveryCost = $deliveryCost;
	}

	/**
	 * Sets invoice number.
	 *
	 * @param string $invoiceNumber Invoice number.
	 * @return void
	 */
	public function setInvoiceNumber( string $invoiceNumber ): void {
		$this->invoiceNumber = $invoiceNumber;
	}

	/**
	 * Sets invoice issue date.
	 *
	 * @param \DateTimeImmutable $invoiceIssueDate Invoice issue date.
	 * @return void
	 */
	public function setInvoiceIssueDate( \DateTimeImmutable $invoiceIssueDate ): void {
		$this->invoiceIssueDate = $invoiceIssueDate;
	}
}
