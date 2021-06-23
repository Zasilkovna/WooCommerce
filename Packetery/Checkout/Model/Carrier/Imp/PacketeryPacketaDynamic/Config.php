<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier\Imp\PacketeryPacketaDynamic;

class Config extends \Packetery\Checkout\Model\Carrier\Config\AbstractConfig
{
    public function toArray(): array {
        return $this->data;
    }
}
