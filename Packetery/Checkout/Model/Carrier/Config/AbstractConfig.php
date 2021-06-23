<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier\Config;

/**
 * Represents config of all internal and external carriers
 */
abstract class AbstractConfig
{
    /** @var array  */
    protected $data;

    /**
     * AbstractConfig constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    protected function getConfigData(string $key) {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }

        return null;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->getConfigData('active') === '1';
    }

    /**
     * @return false|\Magento\Framework\Phrase|string
     */
    public function getTitle()
    {
        return ($this->getConfigData('title') ?: __("Packeta"));
    }

    /**
     * @return float|null
     */
    public function getMaxWeight(): ?float
    {
        $value = $this->getConfigData('max_weight');
        return (is_numeric($value) ? (float)$value : null);
    }

    /**
     * @return int|null
     */
    protected function getFreeShippingEnable(): ?int
    {
        $value = $this->getConfigData('free_shipping_enable');
        return (is_numeric($value) ? (int)$value : null);
    }

    /** Order value threshold
     * @return float|null
     */
    public function getFreeShippingThreshold(): ?float
    {
        if ($this->getFreeShippingEnable() === 1) {
            $value = $this->getConfigData('free_shipping_subtotal');
            return (is_numeric($value) ? (float)$value : null);
        }

        return null;
    }

    /**
     * @return array
     */
    public function getAllowedMethods(): array
    {
        $value = $this->getConfigData('allowedMethods');
        return (is_string($value) ? explode(',', $value) : []);
    }
}
