<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Order;

use Magento\Ui\DataProvider\AbstractDataProvider;

class DataProvider extends AbstractDataProvider
{
    /** @var \Packetery\Checkout\Model\ResourceModel\Order\Collection */
    protected $collection;

    /** @var \Magento\Sales\Model\OrderFactory */
    private $orderFactory;

    /**
     * DataProvider constructor.
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $collectionFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $collectionFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
        $this->orderFactory = $orderFactory;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        $result = [];

        foreach ($this->collection->getItems() as $item) {
            $result[$item->getId()]['general'] = $item->getData(); // princing rules
            $orderNumber = $result[$item->getId()]['general']['order_number'];
            $order = $this->orderFactory->create()->loadByIncrementId($orderNumber);

            $shippingAddress = $order->getShippingAddress();
            if ($shippingAddress) {
                $result[$item->getId()]['general']['misc']['country_id'] = $shippingAddress->getCountryId();
            } else {
                $result[$item->getId()]['general']['misc']['country_id'] = null;
            }

            $shippingMethod = $order->getShippingMethod(true);
            if ($shippingMethod) {
                $result[$item->getId()]['general']['misc']['method'] = $shippingMethod->getData('method');
            } else {
                $result[$item->getId()]['general']['misc']['method'] = null;
            }
        }

        return $result;
    }
}
