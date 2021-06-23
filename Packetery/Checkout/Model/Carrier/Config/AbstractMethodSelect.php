<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier\Config;

abstract class AbstractMethodSelect implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Options array
     *
     * @var array
     */
    protected $_options;

    /**
     * @return array
     */
    abstract protected function createOptions(): array;

    /**
     * Return options array
     *
     * @param boolean $isMultiselect
     * @return array
     */
    public function toOptionArray(bool $isMultiselect = false)
    {
        if (!$this->_options) {
            $this->_options = $this->createOptions();
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
