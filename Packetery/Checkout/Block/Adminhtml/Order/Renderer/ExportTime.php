<?php

namespace Packetery\Checkout\Block\Adminhtml\Order\Renderer;

use Magento\Framework\DataObject;

class ExportTime extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{

    public function __construct(
        \Magento\Backend\Block\Context $context,
        array $data = []
    )
    {
        parent::__construct($context, $data);
    }

    // Here we create a link to point the Order View page for the current value
    public function render(DataObject $row)
    {
        $exportedAt = $row->getData('exported_at');
        $isExported = $row->getData('exported');

        return ($isExported && !is_null($exportedAt) ? $this->formatDate($exportedAt, \IntlDateFormatter::MEDIUM, TRUE) : "");
    }
}
