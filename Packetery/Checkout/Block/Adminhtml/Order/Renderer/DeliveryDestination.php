<?php

namespace Packetery\Checkout\Block\Adminhtml\Order\Renderer;

use Magento\Backend\Block\Context;
use Magento\Framework\DataObject;

class DeliveryDestination extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /** @var \Packetery\Checkout\Model\Config\Source\MethodSelect */
    private $methodSelect;

    /**
     * @param Context $context
     * @param \Packetery\Checkout\Model\Config\Source\MethodSelect $methodSelect
     * @param array $data
     */
    public function __construct(Context $context, \Packetery\Checkout\Model\Config\Source\MethodSelect $methodSelect, array $data = [])
    {
        parent::__construct($context, $data);
        $this->methodSelect = $methodSelect;
    }

    /**
     * render address
     * @param  DataObject $row
     * @return string
     */
    public function render(DataObject $row)
    {
        $branchName = (string)$row->getData('point_name');
        $branchId = $row->getData('point_id');

        $resolvedLabel = $this->methodSelect->getLabelByValue($branchName);
        if ($resolvedLabel) {
            return $resolvedLabel;
        }

        return sprintf("%s (%s)", $branchName, $branchId);
    }
}
