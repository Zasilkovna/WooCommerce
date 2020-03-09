<?php

namespace Packetery\Checkout\Block\Adminhtml\Order\Renderer;

use Magento\Framework\DataObject;

class Address extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * render address
     * @param  DataObject $row
     * @return string
     */
    public function render(DataObject $row)
    {
        $street = $row->getData('recipient_street');
        $city = $row->getData('recipient_city');
        $houseNumber = $row->getData('recipient_house_number');
        $zip = $row->getData('recipient_zip');

        $value = <<< HTML
<p>$street $houseNumber</p>
<p>$city, $zip </p>
HTML;

        return $value;
    }
}
