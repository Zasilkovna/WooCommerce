<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Order;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Packetery\Checkout\Model\Carrier\MethodCode;
use Packetery\Checkout\Model\Carrier\Methods;

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

            $shippingMethod = $order->getShippingMethod(true);
            if ($shippingMethod) {
                $methodCode = MethodCode::fromString($shippingMethod->getData('method'));
                $result[$item->getId()]['general']['misc']['isPickupPointDelivery'] = (Methods::isPickupPointDelivery($methodCode->getMethod()) ? '1' : '0');
                $result[$item->getId()]['general']['misc']['isAnyAddressDelivery'] = (Methods::isAnyAddressDelivery($methodCode->getMethod()) ? '1' : '0');
            } else {
                $result[$item->getId()]['general']['misc']['isPickupPointDelivery'] = '0';
                $result[$item->getId()]['general']['misc']['isAnyAddressDelivery'] = '0';
            }
        }

        return $result;
    }
}
