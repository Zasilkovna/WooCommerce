<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class PacketB2BAttributes
{

    /**
     * @var int
     */
    private $addressId;

    /**
     * @var int
     */
    private $count;

    /**
     * @var bool
     */
    private $isReturn;

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

    public function getCount()
    {
        return $this->count;
    }

    public function withCount($count)
    {
        $new = clone $this;
        $new->count = $count;

        return $new;
    }

    public function getIsReturn()
    {
        return $this->isReturn;
    }

    public function withIsReturn($isReturn)
    {
        $new = clone $this;
        $new->isReturn = $isReturn;

        return $new;
    }


}

