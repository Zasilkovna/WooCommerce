<?php

namespace Packetery\Checkout\Block\Adminhtml\Order\Renderer;

use Magento\Framework\DataObject;

class OrderReference extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /** @var \Magento\Sales\Model\OrderFactory */
    protected $orderFactory;

    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        array $data = []
    )
    {
        $this->orderFactory = $orderFactory;
        parent::__construct($context, $data);
    }

    public function render(DataObject $row)
    {
        $orderNumber =  $row->getData('order_number');
        $order = $this->orderFactory->create()->loadByIncrementId($orderNumber);

        $html ='<a href="' . $this->getUrl('sales/order/view', array('order_id' => $order->getId(), 'key' => $this->getCacheKey())) . '" target="_blank" title="' . $orderNumber . '" >' . $orderNumber . '</a>';

        return $html;
    }
}
