<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

use Packetery\Phpro\SoapClient\Type\ResultInterface;

class ShipmentPacketsResult implements ResultInterface
{

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\PacketCollection
     */
    private $packets;

    public function getPackets()
    {
        return $this->packets;
    }

    public function withPackets($packets)
    {
        $new = clone $this;
        $new->packets = $packets;

        return $new;
    }


}

