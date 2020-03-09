<?php

namespace Packetery\Checkout\Block\Adminhtml\Order\Renderer;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

class OrderStatus extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
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

    /**
     * @param \Magento\Framework\DataObject $row
     *
     * @return string|null
     */
    public function render(DataObject $row)
    {
        $orderNumber = $row->getData('order_number');
        $order = $this->orderFactory->create()->loadByIncrementId($orderNumber);

        try {
            return $order->getStatusLabel();
        }
        catch (LocalizedException $e) {
            return null;
        }
    }
}
