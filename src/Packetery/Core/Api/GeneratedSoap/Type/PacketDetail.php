<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

use Packetery\Phpro\SoapClient\Type\ResultInterface;

class PacketDetail implements ResultInterface
{

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $barcode;

    /**
     * @var string
     */
    private $barcodeText;

    /**
     * @var string
     */
    private $password;

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

    public function getPassword()
    {
        return $this->password;
    }

    public function withPassword($password)
    {
        $new = clone $this;
        $new->password = $password;

        return $new;
    }


}

