<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model;

class Pricingrule extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'packetery_checkout_pricingrule';

    protected $_cacheTag = 'packetery_checkout_pricingrule';

    protected $_eventPrefix = 'packetery_checkout_pricingrule';

    protected function _construct()
    {
        $this->_init('Packetery\Checkout\Model\ResourceModel\Pricingrule');
    }

    /**
     * @return string[]
     */
    public function getIdentities(): array
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * @return array
     */
    public function getDefaultValues(): array
    {
        $values = [];

        return $values;
    }

    /**
     * @return float|null
     */
    public function getFreeShipment(): ?float
    {
        $value = $this->getData('free_shipment') ?: null;
        return $value === null ? null : (float)$value;
    }

    /**
     * @return string|null
     */
    public function getCountryId(): ?string
    {
        $value = $this->getData('free_shipment') ?: null;
        return $value === null ? null : (string)$value;
    }
}
