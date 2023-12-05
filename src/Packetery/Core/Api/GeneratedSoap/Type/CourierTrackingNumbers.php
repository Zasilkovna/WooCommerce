<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class CourierTrackingNumbers
{

    /**
     * @var string
     */
    private $courierTrackingNumber;

    public function getCourierTrackingNumber()
    {
        return $this->courierTrackingNumber;
    }

    public function withCourierTrackingNumber($courierTrackingNumber)
    {
        $new = clone $this;
        $new->courierTrackingNumber = $courierTrackingNumber;

        return $new;
    }


}

