<?php
namespace Packetery\Checkout\Model\ResourceModel\Pricingrule;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /** @var string  */
    protected $_idFieldName = 'id';

    /** @var string  */
    protected $_eventPrefix = 'packetery_checkout_pricingrule_collection';

    /** @var string  */
    protected $_eventObject = 'pricingrule_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Packetery\Checkout\Model\Pricingrule', 'Packetery\Checkout\Model\ResourceModel\Pricingrule');
    }

    /**
     * @return \Packetery\Checkout\Model\Pricingrule[]
     */
    public function getItems()
    {
        return parent::getItems();
    }

    /**
     * @return \Packetery\Checkout\Model\Pricingrule|null
     */
    public function getFirstRecord(): ?\Packetery\Checkout\Model\Pricingrule
    {
        $this->load();
        $items = $this->_items;
        return array_shift($items);
    }
}
