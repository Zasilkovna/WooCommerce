<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier\Imp\PacketeryPacketaDynamic;

use Packetery\Checkout\Model\Carrier\Config\AbstractMethodSelect;
use Packetery\Checkout\Model\Carrier\Methods;

class MethodSelect extends AbstractMethodSelect implements \Magento\Framework\Data\OptionSourceInterface
{
    protected function createOptions(): array
    {
        return [
            ['value' => Methods::DIRECT_ADDRESS_DELIVERY, 'label' => __('Address Delivery')],
        ];
    }
}

