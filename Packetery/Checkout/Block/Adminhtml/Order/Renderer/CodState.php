<?php

namespace Packetery\Checkout\Block\Adminhtml\Order\Renderer;

use Magento\Framework\DataObject;

class CodState extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    public function render(DataObject $row)
    {
        $cod = $row->getData('cod');

        return ($cod > 0 ? __('Yes') : __('No'));
    }
}
