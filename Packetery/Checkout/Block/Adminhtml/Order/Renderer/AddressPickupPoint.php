<?php

namespace Packetery\Checkout\Block\Adminhtml\Order\Renderer;

use Magento\Framework\DataObject;

class AddressPickupPoint extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * render address
     * @param  DataObject $row
     * @return string
     */
    public function render(DataObject $row)
    {

        $branchName = $row->getData('point_name');
        $branchId = $row->getData('point_id');

        return sprintf("%s (%s)", $branchName, $branchId);
    }
}
