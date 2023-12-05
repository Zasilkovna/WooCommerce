<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

use Packetery\Phpro\SoapClient\Type\ResultInterface;

class PacketInfoResult implements ResultInterface
{

    /**
     * @var int
     */
    private $branchId;

    /**
     * @var int
     */
    private $invoicedWeightGrams;

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\CourierInfo
     */
    private $courierInfo;

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\Services
     */
    private $services;

    public function getBranchId()
    {
        return $this->branchId;
    }

    public function withBranchId($branchId)
    {
        $new = clone $this;
        $new->branchId = $branchId;

        return $new;
    }

    public function getInvoicedWeightGrams()
    {
        return $this->invoicedWeightGrams;
    }

    public function withInvoicedWeightGrams($invoicedWeightGrams)
    {
        $new = clone $this;
        $new->invoicedWeightGrams = $invoicedWeightGrams;

        return $new;
    }

    public function getCourierInfo()
    {
        return $this->courierInfo;
    }

    public function withCourierInfo($courierInfo)
    {
        $new = clone $this;
        $new->courierInfo = $courierInfo;

        return $new;
    }

    public function getServices()
    {
        return $this->services;
    }

    public function withServices($services)
    {
        $new = clone $this;
        $new->services = $services;

        return $new;
    }


}

