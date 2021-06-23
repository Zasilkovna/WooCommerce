<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\CarrierCountry\Listing\Filter;

class AvailableSelect implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray(): array {
        $options = [];

        $options[] = ['label' => __('Yes'), 'value' => 1];
        $options[] = ['label' => __('No'), 'value' => 0];

        return $options;
    }
}
