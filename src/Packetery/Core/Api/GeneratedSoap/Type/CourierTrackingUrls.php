<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class CourierTrackingUrls
{

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\CourierTrackingUrl
     */
    private $courierTrackingUrl;

    public function getCourierTrackingUrl()
    {
        return $this->courierTrackingUrl;
    }

    public function withCourierTrackingUrl($courierTrackingUrl)
    {
        $new = clone $this;
        $new->courierTrackingUrl = $courierTrackingUrl;

        return $new;
    }


}

