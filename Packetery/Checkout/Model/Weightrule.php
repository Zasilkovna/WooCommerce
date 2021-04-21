<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model;

class Weightrule extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'packetery_checkout_weightrule';

    protected $_cacheTag = 'packetery_checkout_weightrule';

    protected $_eventPrefix = 'packetery_checkout_weightrule';

    /**
     *  init
     */
    protected function _construct()
    {
        $this->_init('Packetery\Checkout\Model\ResourceModel\Weightrule');
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
     * @return float
     */
    public function getPrice(): float
    {
        $price = $this->getData('price');
        return (float) $price;
    }

    /**
     * @return float|null
     */
    public function getMaxWeight(): ?float
    {
        $weight = $this->getData('max_weight');
        return (is_numeric($weight) ? (float) $weight : null);
    }
}
