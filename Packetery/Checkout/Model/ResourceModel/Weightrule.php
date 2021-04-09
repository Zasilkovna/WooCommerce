<?php
namespace Packetery\Checkout\Model\ResourceModel;

class Weightrule extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Weightrule constructor.
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
        $this->_init('packetery_weight_rule', 'id');
    }
}
