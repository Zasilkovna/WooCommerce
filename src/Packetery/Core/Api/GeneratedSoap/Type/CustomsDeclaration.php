<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class CustomsDeclaration
{

    /**
     * @var string
     */
    private $ead;

    /**
     * @var float
     */
    private $deliveryCost;

    /**
     * @var bool
     */
    private $isDocument;

    /**
     * @var string
     */
    private $invoiceNumber;

    /**
     * @var \DateTimeInterface
     */
    private $invoiceIssueDate;

    /**
     * @var string
     */
    private $mrn;

    /**
     * @var int
     */
    private $eadFile;

    /**
     * @var int
     */
    private $invoiceFile;

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\CustomsDeclarationItems
     */
    private $items;

    public function getEad()
    {
        return $this->ead;
    }

    public function withEad($ead)
    {
        $new = clone $this;
        $new->ead = $ead;

        return $new;
    }

    public function getDeliveryCost()
    {
        return $this->deliveryCost;
    }

    public function withDeliveryCost($deliveryCost)
    {
        $new = clone $this;
        $new->deliveryCost = $deliveryCost;

        return $new;
    }

    public function getIsDocument()
    {
        return $this->isDocument;
    }

    public function withIsDocument($isDocument)
    {
        $new = clone $this;
        $new->isDocument = $isDocument;

        return $new;
    }

    public function getInvoiceNumber()
    {
        return $this->invoiceNumber;
    }

    public function withInvoiceNumber($invoiceNumber)
    {
        $new = clone $this;
        $new->invoiceNumber = $invoiceNumber;

        return $new;
    }

    public function getInvoiceIssueDate()
    {
        return $this->invoiceIssueDate;
    }

    public function withInvoiceIssueDate($invoiceIssueDate)
    {
        $new = clone $this;
        $new->invoiceIssueDate = $invoiceIssueDate;

        return $new;
    }

    public function getMrn()
    {
        return $this->mrn;
    }

    public function withMrn($mrn)
    {
        $new = clone $this;
        $new->mrn = $mrn;

        return $new;
    }

    public function getEadFile()
    {
        return $this->eadFile;
    }

    public function withEadFile($eadFile)
    {
        $new = clone $this;
        $new->eadFile = $eadFile;

        return $new;
    }

    public function getInvoiceFile()
    {
        return $this->invoiceFile;
    }

    public function withInvoiceFile($invoiceFile)
    {
        $new = clone $this;
        $new->invoiceFile = $invoiceFile;

        return $new;
    }

    public function getItems()
    {
        return $this->items;
    }

    public function withItems($items)
    {
        $new = clone $this;
        $new->items = $items;

        return $new;
    }


}

