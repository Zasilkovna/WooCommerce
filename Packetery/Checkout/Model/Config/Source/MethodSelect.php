<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Config\Source;

use Packetery\Checkout\Model\Carrier\Config\AllowedMethods;

class MethodSelect implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options array
     *
     * @var array
     */
    protected $_options;

    /**
     * Return options array
     *
     * @param boolean $isMultiselect
     * @return array
     */
    public function toOptionArray($isMultiselect = false)
    {
        if (!$this->_options) {
            $this->_options = [
                ['value' => AllowedMethods::PICKUP_POINT_DELIVERY, 'label' => __('Pickup Point Delivery Method')],
                ['value' => AllowedMethods::ADDRESS_DELIVERY, 'label' => __('Address Delivery Method')],
            ];
        }

        $options = $this->_options;
        if (!$isMultiselect) {
            array_unshift($options, ['value' => '', 'label' => __('Select option')]);
        }

        return $options;
    }

    /**
     * @return array
     */
    public function getMethods(): array
    {
        return array_map(
            function (array $option) {
                return $option['value'];
            },
            $this->toOptionArray(true)
        );
    }

    /**
     * @param string $value
     * @return \Magento\Framework\Phrase|null
     */
    public function getLabelByValue(string $value): ?\Magento\Framework\Phrase
    {
        $options = $this->toOptionArray();

        foreach ($options as $option) {
            if ($option['value'] === $value) {
                return $option['label'];
            }
        }

        return null;
    }
}
