<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

use Packetery\Phpro\SoapClient\Type\ResultInterface;

class CurrentStatusRecord implements ResultInterface
{

    /**
     * @var bool
     */
    private $isReturning;

    /**
     * @var \DateTimeInterface
     */
    private $storedUntil;

    /**
     * @var string
     */
    private $carrierId;

    /**
     * @var string
     */
    private $carrierName;

    public function getIsReturning()
    {
        return $this->isReturning;
    }

    public function withIsReturning($isReturning)
    {
        $new = clone $this;
        $new->isReturning = $isReturning;

        return $new;
    }

    public function getStoredUntil()
    {
        return $this->storedUntil;
    }

    public function withStoredUntil($storedUntil)
    {
        $new = clone $this;
        $new->storedUntil = $storedUntil;

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

