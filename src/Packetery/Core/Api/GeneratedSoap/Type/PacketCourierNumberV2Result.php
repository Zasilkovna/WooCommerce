<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

use Packetery\Phpro\SoapClient\Type\ResultInterface;

class PacketCourierNumberV2Result implements ResultInterface
{

    /**
     * @var string
     */
    private $courierNumber;

    /**
     * @var string
     */
    private $carrierId;

    /**
     * @var string
     */
    private $carrierName;

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

    public function getCarrierId()
    {
        return $this->carrierId;
    }

    public function withCarrierId($carrierId)
    {
        $new = clone $this;
        $new->carrierId = $carrierId;

        return $new;
    }

    public function getCarrierName()
    {
        return $this->carrierName;
    }

    public function withCarrierName($carrierName)
    {
        $new = clone $this;
        $new->carrierName = $carrierName;

        return $new;
    }


}

