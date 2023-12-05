<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

use Packetery\Phpro\SoapClient\Type\ResultInterface;

class ShipmentIdDetail implements ResultInterface
{

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $checksum;

    /**
     * @var string
     */
    private $barcode;

    /**
     * @var string
     */
    private $barcodeText;

    public function getId()
    {
        return $this->id;
    }

    public function withId($id)
    {
        $new = clone $this;
        $new->id = $id;

        return $new;
    }

    public function getChecksum()
    {
        return $this->checksum;
    }

    public function withChecksum($checksum)
    {
        $new = clone $this;
        $new->checksum = $checksum;

        return $new;
    }

    public function getBarcode()
    {
        return $this->barcode;
    }

    public function withBarcode($barcode)
    {
        $new = clone $this;
        $new->barcode = $barcode;

        return $new;
    }

    public function getBarcodeText()
    {
        return $this->barcodeText;
    }

    public function withBarcodeText($barcodeText)
    {
        $new = clone $this;
        $new->barcodeText = $barcodeText;

        return $new;
    }


}

