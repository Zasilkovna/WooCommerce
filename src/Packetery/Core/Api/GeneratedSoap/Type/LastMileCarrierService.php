<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class LastMileCarrierService
{

    /**
     * @var int
     */
    private $addressId;

    /**
     * @var string
     */
    private $foreignId;

    /**
     * @var string
     */
    private $barcode;

    /**
     * @var string
     */
    private $trackingCode;

    public function getAddressId()
    {
        return $this->addressId;
    }

    public function withAddressId($addressId)
    {
        $new = clone $this;
        $new->addressId = $addressId;

        return $new;
    }

    public function getForeignId()
    {
        return $this->foreignId;
    }

    public function withForeignId($foreignId)
    {
        $new = clone $this;
        $new->foreignId = $foreignId;

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

    public function getTrackingCode()
    {
        return $this->trackingCode;
    }

    public function withTrackingCode($trackingCode)
    {
        $new = clone $this;
        $new->trackingCode = $trackingCode;

        return $new;
    }


}

