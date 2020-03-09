<?php
namespace Packetery\Checkout\Model;

class Order extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'packetery_checkout_order';

    protected $_cacheTag = 'packetery_checkout_order';

    protected $_eventPrefix = 'packetery_checkout_order';

    protected function _construct()
    {
        $this->_init('Packetery\Checkout\Model\ResourceModel\Order');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getDefaultValues()
    {
        $values = [];

        return $values;
    }
}
