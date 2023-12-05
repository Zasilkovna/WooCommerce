<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class Ids
{

    /**
     * @var string
     */
    private $packetId;

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


}

