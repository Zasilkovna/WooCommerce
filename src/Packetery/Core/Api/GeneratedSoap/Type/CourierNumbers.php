<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class CourierNumbers
{

    /**
     * @var string
     */
    private $courierNumber;

    public function getCourierNumber()
    {
        return $this->courierNumber;
    }

    public function withCourierNumber($courierNumber)
    {
        $new = clone $this;
        $new->courierNumber = $courierNumber;

        return $new;
    }


}

