<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier\Imp\Packetery;

use Packetery\Checkout\Model\Carrier\Config\AbstractMethodSelect;
use Packetery\Checkout\Model\Carrier\Methods;

class MethodSelect extends AbstractMethodSelect implements \Magento\Framework\Data\OptionSourceInterface
{
    protected function createOptions(): array
    {
        return [
            ['value' => Methods::PICKUP_POINT_DELIVERY, 'label' => __('Pickup Point Delivery')],
            ['value' => Methods::ADDRESS_DELIVERY, 'label' => __('Best Address Delivery')],
        ];
    }
}
