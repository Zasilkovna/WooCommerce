<?php

namespace Packetery\Checkout\Block\Adminhtml\Order\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use Packetery\Checkout\Model\Carrier\Config\AllowedMethods;

class Actions extends AbstractRenderer
{
    /** @var \Magento\Sales\Model\OrderFactory */
    protected $orderFactory;

    /**
     * Actions constructor.
     *
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        array $data = []
    )
    {
        $this->orderFactory = $orderFactory;
        parent::__construct($context, $data);
    }

    /**
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(DataObject $row): string
    {
        $orderNumber =  $row->getData('order_number');
        $order = $this->orderFactory->create()->loadByIncrementId($orderNumber);
        $shippingMethod = $order->getShippingMethod(true);

        $html = '';

        if ($shippingMethod && ($shippingMethod->getData('method') === AllowedMethods::PICKUP_POINT_DELIVERY || $shippingMethod->getData('method') === 'packetery')) {
            $html = '<a href="' . $this->getUrl('packetery/order/detail', array('id' => $row->getData('id'))) . '" title="' . __('Edit') . '" >' . __('Edit') . '</a>';
        }

        return $html;
    }
}
