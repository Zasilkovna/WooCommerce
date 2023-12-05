<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class CreatePacketResult
{

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\PacketIdDetail
     */
    private $createdPacket;

    /**
     * @var string
     */
    private $fault;

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\PacketAttributesFault
     */
    private $packetAttributesFault;

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\DispatchOrderUnknownCodeFault
     */
    private $dispatchOrderUnknownCodeFault;

    public function getCreatedPacket()
    {
        return $this->createdPacket;
    }

    public function withCreatedPacket($createdPacket)
    {
        $new = clone $this;
        $new->createdPacket = $createdPacket;

        return $new;
    }

    public function getFault()
    {
        return $this->fault;
    }

    public function withFault($fault)
    {
        $new = clone $this;
        $new->fault = $fault;

        return $new;
    }

    public function getPacketAttributesFault()
    {
        return $this->packetAttributesFault;
    }

    public function withPacketAttributesFault($packetAttributesFault)
    {
        $new = clone $this;
        $new->packetAttributesFault = $packetAttributesFault;

        return $new;
    }

    public function getDispatchOrderUnknownCodeFault()
    {
        return $this->dispatchOrderUnknownCodeFault;
    }

    public function withDispatchOrderUnknownCodeFault($dispatchOrderUnknownCodeFault)
    {
        $new = clone $this;
        $new->dispatchOrderUnknownCodeFault = $dispatchOrderUnknownCodeFault;

        return $new;
    }


}

