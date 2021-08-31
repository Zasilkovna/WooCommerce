<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\Order\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class OrderReference extends Column
{
    /** @var UrlInterface */
    private $_urlBuilder;

    /** @var \Magento\Sales\Model\OrderFactory */
    private $orderFactory;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context
     * @param \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory
     * @param \Magento\Backend\Model\UrlInterface $urlBuilder
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        array $components = [],
        array $data = []
    ) {
        $this->_urlBuilder = $urlBuilder;
        $this->orderFactory = $orderFactory;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $name = $this->getData('name');
                $orderNumber = $item['order_number'];
                $order = $this->orderFactory->create()->loadByIncrementId($orderNumber);
                $url = $this->_urlBuilder->getUrl('sales/order/view', ['order_id' => $order->getId()]);
                $item[$name] = '<a href="' . $url . '" target="_blank">' . htmlentities($orderNumber) . '</a>';
            }
        }

        return $dataSource;
    }
}
