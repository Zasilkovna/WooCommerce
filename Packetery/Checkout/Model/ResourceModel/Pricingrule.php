<?php
namespace Packetery\Checkout\Model\ResourceModel;

class Pricingrule extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Pricingrule constructor.
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context
    ) {
        parent::__construct($context);
    }

    /**
     *  init
     */
    protected function _construct()
    {
        $this->_init('packetery_pricing_rule', 'id');
    }
}
