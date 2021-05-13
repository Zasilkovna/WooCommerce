<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier;

use Packetery\Checkout\Model\Carrier\Config\AllowedMethods;

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
        return ($this->packeteryCarrier->getConfigData('api_key') ?: null);
    }

    /**
     * @return false|\Magento\Framework\Phrase|string
     */
    public function getTitle()
    {
        return ($this->packeteryCarrier->getConfigData('title') ?: __("Packeta"));
    }

    /**
     * @return string[]
     */
    public function getCodMethods(): array
    {
        $value = $this->packeteryCarrier->getConfigData('cod_methods');
        return (is_string($value) ? explode(',', $value) : []);
    }

    /**
     * @return float|null
     */
    public function getDefaultPrice(): ?float
    {
        $value = $this->packeteryCarrier->getConfigData('default_price');
        return (is_numeric($value) ? (float)$value : null);
    }

    /** kilos
     * @return float|null
     */
    public function getMaxWeight(): ?float
    {
        $value = $this->packeteryCarrier->getConfigData('max_weight');
        return (is_numeric($value) ? (float)$value : null);
    }

    /**
     * @return int|null
     */
    private function getFreeShippingEnable(): ?int
    {
        $value = $this->packeteryCarrier->getConfigData('free_shipping_enable');
        return (is_numeric($value) ? (int)$value : null);
    }

    /** Order value threshold
     * @return float|null
     */
    public function getFreeShippingThreshold(): ?float
    {
        if ($this->getFreeShippingEnable() === 1) {
            $value = $this->packeteryCarrier->getConfigData('free_shipping_subtotal');
            return (is_numeric($value) ? (float)$value : null);
        }

        return null;
    }

    /** 1 => Specific countries
     *  0 => All countries
     * @return int|null
     */
    public function getApplicableCountries(): int
    {
        $value = $this->packeteryCarrier->getConfigData('sallowspecific'); // "Use system value" resolves in 0
        if (is_numeric($value)) {
            return (int)$value;
        }

        throw new \Exception('sallowspecific config value was not restored');
    }

    /** Collection of allowed countries
     * @return array
     */
    public function getSpecificCountries(): array
    {
        $value = $this->packeteryCarrier->getConfigData('specificcountry');
        return (is_string($value) ? explode(',', $value) : []);
    }

    /**
     * @return \Packetery\Checkout\Model\Carrier\Config\AllowedMethods
     */
    public function getAllowedMethods(): AllowedMethods
    {
        $value = $this->packeteryCarrier->getConfigData('allowedMethods');
        $methods = (is_string($value) ? explode(',', $value) : []);
        return new AllowedMethods($methods);
    }

    /**
     * @param string $countryId
     * @return bool
     */
    public function hasSpecificCountryAllowed(string $countryId): bool
    {
        if ($this->getApplicableCountries() === 1) {
            $countries = $this->getSpecificCountries();
            return empty($countries) || in_array($countryId, $countries);
        }

        if ($this->getApplicableCountries() === 0) {
            return true;
        }

        throw new \Exception('Unrecognized getApplicableCountries return value');
    }
}
