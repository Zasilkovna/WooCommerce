<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

use Packetery\Phpro\SoapClient\Type\ResultInterface;

class CreatePacketsB2BResults implements ResultInterface
{

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\CreatePacketResult
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

