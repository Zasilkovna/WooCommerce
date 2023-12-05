<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class PacketCollection
{

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\PacketIdDetail
     */
    private $packet;

    public function getPacket()
    {
        return $this->packet;
    }

    public function withPacket($packet)
    {
        $new = clone $this;
        $new->packet = $packet;

        return $new;
    }


}

