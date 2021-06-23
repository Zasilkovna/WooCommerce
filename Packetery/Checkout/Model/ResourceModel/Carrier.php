<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\ResourceModel;

class Carrier extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct() {
        $this->_init('packetery_carrier', 'id');
    }
}
