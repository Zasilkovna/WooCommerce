<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\Order\Listing\Filter;

class OrderStatusSelect implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Status\Collection $statusCollectionFactory
     */
    protected $statusCollectionFactory;

    /**
     * OrderStatusSelect constructor.
     *
     * @param \Magento\Sales\Model\ResourceModel\Order\Status\Collection $statusCollectionFactory
     */
    public function __construct(\Magento\Sales\Model\ResourceModel\Order\Status\Collection $statusCollectionFactory) {
        $this->statusCollectionFactory = $statusCollectionFactory;
    }

    public function toOptionArray() {
        return $this->statusCollectionFactory->toOptionArray();
    }
}
