<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class CourierInfo
{

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\CourierInfoItem
     */
    private $courierInfoItem;

    public function getCourierInfoItem()
    {
        return $this->courierInfoItem;
    }

    public function withCourierInfoItem($courierInfoItem)
    {
        $new = clone $this;
        $new->courierInfoItem = $courierInfoItem;

        return $new;
    }


}

