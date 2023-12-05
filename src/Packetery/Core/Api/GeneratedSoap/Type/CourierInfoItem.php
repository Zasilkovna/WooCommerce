<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class CourierInfoItem
{

    /**
     * @var int
     */
    private $courierId;

    /**
     * @var string
     */
    private $courierName;

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\CourierNumbers
     */
    private $courierNumbers;

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\CourierBarcodes
     */
    private $courierBarcodes;

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\CourierTrackingNumbers
     */
    private $courierTrackingNumbers;

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\CourierTrackingUrls
     */
    private $courierTrackingUrls;

    public function getCourierId()
    {
        return $this->courierId;
    }

    public function withCourierId($courierId)
    {
        $new = clone $this;
        $new->courierId = $courierId;

        return $new;
    }

    public function getCourierName()
    {
        return $this->courierName;
    }

    public function withCourierName($courierName)
    {
        $new = clone $this;
        $new->courierName = $courierName;

        return $new;
    }

    public function getCourierNumbers()
    {
        return $this->courierNumbers;
    }

    public function withCourierNumbers($courierNumbers)
    {
        $new = clone $this;
        $new->courierNumbers = $courierNumbers;

        return $new;
    }

    public function getCourierBarcodes()
    {
        return $this->courierBarcodes;
    }

    public function withCourierBarcodes($courierBarcodes)
    {
        $new = clone $this;
        $new->courierBarcodes = $courierBarcodes;

        return $new;
    }

    public function getCourierTrackingNumbers()
    {
        return $this->courierTrackingNumbers;
    }

    public function withCourierTrackingNumbers($courierTrackingNumbers)
    {
        $new = clone $this;
        $new->courierTrackingNumbers = $courierTrackingNumbers;

        return $new;
    }

    public function getCourierTrackingUrls()
    {
        return $this->courierTrackingUrls;
    }

    public function withCourierTrackingUrls($courierTrackingUrls)
    {
        $new = clone $this;
        $new->courierTrackingUrls = $courierTrackingUrls;

        return $new;
    }


}

