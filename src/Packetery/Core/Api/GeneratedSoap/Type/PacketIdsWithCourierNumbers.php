<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class PacketIdsWithCourierNumbers
{

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\PacketIdWithCourierNumber
     */
    private $packetIdWithCourierNumber;

    public function getPacketIdWithCourierNumber()
    {
        return $this->packetIdWithCourierNumber;
    }

    public function withPacketIdWithCourierNumber($packetIdWithCourierNumber)
    {
        $new = clone $this;
        $new->packetIdWithCourierNumber = $packetIdWithCourierNumber;

        return $new;
    }


}

