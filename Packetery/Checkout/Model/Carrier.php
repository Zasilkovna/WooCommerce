<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model;

class Carrier extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'packetery_checkout_carrier';

    protected $_cacheTag = 'packetery_checkout_carrier';

    protected $_eventPrefix = 'packetery_checkout_carrier';

    protected function _construct()
    {
        $this->_init('Packetery\Checkout\Model\ResourceModel\Carrier');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getDefaultValues()
    {
        return [];
    }

    /**
     * @return string
     */
    public function getMethod(): string {
        $isPickupPoints = (bool)$this->getData('is_pickup_points');
        if ($isPickupPoints) {
            return \Packetery\Checkout\Model\Carrier\Methods::PICKUP_POINT_DELIVERY;
        }

        return \Packetery\Checkout\Model\Carrier\Methods::DIRECT_ADDRESS_DELIVERY;
    }

    /**
     * @return int
     */
    public function getCarrierId(): int {
        return (int)$this->getData('carrier_id');
    }

    /**
     * @return string
     */
    public function getName(): string {
        return (string)$this->getData('name');
    }

    /**
     * @return string
     */
    public function getCarrierName(): string {
        return (string)$this->getData('carrier_name');
    }

    /**
     * @return string
     */
    public function getFinalCarrierName(): string {
        return ($this->getCarrierName() ?: $this->getName());
    }

    /**
     * @return string
     */
    public function getCountry(): string {
        return (string)$this->getData('country');
    }

    /**
     * @return float
     */
    public function getMaxWeight(): float {
        return (float)$this->getData('max_weight');
    }

    /**
     * @return bool
     */
    public function getDeleted(): bool {
        return (bool)$this->getData('deleted');
    }
}
