<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class CourierBarcodes
{

    /**
     * @var string
     */
    private $courierBarcode;

    public function getCourierBarcode()
    {
        return $this->courierBarcode;
    }

    public function withCourierBarcode($courierBarcode)
    {
        $new = clone $this;
        $new->courierBarcode = $courierBarcode;

        return $new;
    }


}

