<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class PacketIdWithCourierNumber
{

    /**
     * @var string
     */
    private $packetId;

    /**
     * @var string
     */
    private $courierNumber;

    public function getPacketId()
    {
        return $this->packetId;
    }

    public function withPacketId($packetId)
    {
        $new = clone $this;
        $new->packetId = $packetId;

        return $new;
    }

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

