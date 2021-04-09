<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier;

class PacketeryConfig
{
    /** @var \Packetery\Checkout\Model\Carrier\Packetery  */
    private $packeteryCarrier;

    /**
     * PacketeryConfig constructor.
     *
     * @param \Packetery\Checkout\Model\Carrier\Packetery $packeteryCarrier
     */
    public function __construct(Packetery $packeteryCarrier)
    {
        $this->packeteryCarrier = $packeteryCarrier;
    }

    /**
     * @return string|null
     */
    public function getApiKey(): ?string
    {
        return $this->packeteryCarrier->getConfigData('api_key') ?: null;
    }

    /**
     * @return false|\Magento\Framework\Phrase|string
     */
    public function getTitle()
    {
        return $this->packeteryCarrier->getConfigData('title') ?: __("Packeta");
    }

    /**
     * @return false|\Magento\Framework\Phrase|string
     */
    public function getName()
    {
        return $this->packeteryCarrier->getConfigData('name') ?: __("Z-Point");
    }

    /**
     * @return string[]
     */
    public function getCodMethods(): array
    {
        $value = $this->packeteryCarrier->getConfigData('cod_methods');
        return is_string($value) ? explode(',', $value) : [];
    }

    /**
     * @return float|null
     */
    public function getDefaultPrice(): ?float
    {
        $value = $this->packeteryCarrier->getConfigData('default_price');
        return is_numeric($value) ? (float)$value : null;
    }

    /** kilos
     * @return float|null
     */
    public function getMaxWeight(): ?float
    {
        $value = $this->packeteryCarrier->getConfigData('max_weight');
        return is_numeric($value) ? (float)$value : null;
    }

    /**
     * @return int|null
     */
    private function getFreeShippingEnable(): ?int
    {
        $value = $this->packeteryCarrier->getConfigData('free_shipping_enable');
        return is_numeric($value) ? (int)$value : null;
    }

    /** Order value threshold
     * @return float|null
     */
    public function getFreeShippingThreshold(): ?float
    {
        if ($this->getFreeShippingEnable() === 1) {
            $value = $this->packeteryCarrier->getConfigData('free_shipping_subtotal');
            return is_numeric($value) ? (float)$value : null;
        }

        return null;
    }

    /** 1 => Specific countries
     *  0 => All countries
     *  null => unspecified (most likely system specific value)
     * @return int|null
     */
    public function getApplicableCountries(): ?int
    {
        $value = $this->packeteryCarrier->getConfigData('sallowspecific');
        return is_numeric($value) ? (int)$value : null;
    }

    /** Collection of allowed countries
     * @return array
     */
    public function getSpecificCountries(): array
    {
        $value = $this->packeteryCarrier->getConfigData('specificcountry');
        return is_string($value) ? explode(',', $value) : [];
    }
}
