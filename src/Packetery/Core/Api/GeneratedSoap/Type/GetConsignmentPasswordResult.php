<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class GetConsignmentPasswordResult
{

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\PacketConsignerDetail
     */
    private $packetConsignerDetail;

    /**
     * @var string
     */
    private $fault;

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\IncorrectApiPasswordFault
     */
    private $IncorrectApiPasswordFault;

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\PacketIdFault
     */
    private $PacketIdFault;

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\AccessDeniedFault
     */
    private $AccessDeniedFault;

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\PacketAttributesFault
     */
    private $PacketAttributesFault;

    public function getPacketConsignerDetail()
    {
        return $this->packetConsignerDetail;
    }

    public function withPacketConsignerDetail($packetConsignerDetail)
    {
        $new = clone $this;
        $new->packetConsignerDetail = $packetConsignerDetail;

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

    public function getIncorrectApiPasswordFault()
    {
        return $this->IncorrectApiPasswordFault;
    }

    public function withIncorrectApiPasswordFault($IncorrectApiPasswordFault)
    {
        $new = clone $this;
        $new->IncorrectApiPasswordFault = $IncorrectApiPasswordFault;

        return $new;
    }

    public function getPacketIdFault()
    {
        return $this->PacketIdFault;
    }

    public function withPacketIdFault($PacketIdFault)
    {
        $new = clone $this;
        $new->PacketIdFault = $PacketIdFault;

        return $new;
    }

    public function getAccessDeniedFault()
    {
        return $this->AccessDeniedFault;
    }

    public function withAccessDeniedFault($AccessDeniedFault)
    {
        $new = clone $this;
        $new->AccessDeniedFault = $AccessDeniedFault;

        return $new;
    }

    public function getPacketAttributesFault()
    {
        return $this->PacketAttributesFault;
    }

    public function withPacketAttributesFault($PacketAttributesFault)
    {
        $new = clone $this;
        $new->PacketAttributesFault = $PacketAttributesFault;

        return $new;
    }


}

