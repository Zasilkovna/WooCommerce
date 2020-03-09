<?php

namespace Packetery\Checkout\Block\Adminhtml\Order\Renderer;

use Magento\Framework\DataObject;

class CreatedDate extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /** @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface */
    private $timezone;

    /** @var \Magento\Sales\Model\OrderFactory */
    private $orderFactory;

    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->timezone = $timezone;
        $this->orderFactory = $orderFactory;
    }

    public function render(DataObject $row)
    {
        $orderNumber = $row->getData('order_number');
        $order = $this->orderFactory->create()->loadByIncrementId($orderNumber);
        $created_at = $order->getCreatedAt();

        return $this->timezone->formatDate($created_at, \IntlDateFormatter::MEDIUM, TRUE);
    }
}
