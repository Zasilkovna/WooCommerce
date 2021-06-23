<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier\Imp\Packetery;

class Config extends \Packetery\Checkout\Model\Carrier\Config\AbstractConfig
{
    /**
     * @return string|null
     */
    public function getApiKey(): ?string
    {
        return ($this->getConfigData('api_key') ?: null);
    }

    /**
     * @return string[]
     */
    public function getCodMethods(): array
    {
        $value = $this->getConfigData('cod_methods');
        return (is_string($value) ? explode(',', $value) : []);
    }
}
