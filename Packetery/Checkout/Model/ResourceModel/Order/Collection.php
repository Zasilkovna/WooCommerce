<?php
namespace Packetery\Checkout\Model\ResourceModel\Order;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'packetery_checkout_order_collection';
    protected $_eventObject = 'order_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Packetery\Checkout\Model\Order', 'Packetery\Checkout\Model\ResourceModel\Order');
    }
}
