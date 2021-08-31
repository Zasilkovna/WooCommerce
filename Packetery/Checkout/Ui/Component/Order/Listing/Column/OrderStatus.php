<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\Order\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\Listing\Columns\Column;

class OrderStatus extends Column
{
    /** @var \Magento\Sales\Model\OrderFactory */
    protected $orderFactory;

    /**
     * Country constructor.
     *
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context
     * @param \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->orderFactory = $orderFactory;
    }

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $orderNumber = $item['order_number'];
                $order = $this->orderFactory->create()->loadByIncrementId($orderNumber);

                try {
                    $item[$this->getData('name')] = $order->getStatusLabel();
                } catch (LocalizedException $e) {
                }
            }
        }

        return $dataSource;
    }

    protected function applySorting() {
        // no DB select sorting
    }
}
