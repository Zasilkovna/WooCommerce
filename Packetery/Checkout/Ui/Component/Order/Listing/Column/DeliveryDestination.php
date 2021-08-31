<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\Order\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Packetery\Checkout\Model\Carrier\MethodCode;
use Packetery\Checkout\Model\Carrier\Methods;
use Packetery\Checkout\Model\Misc\ComboPhrase;

class DeliveryDestination extends Column
{
    /** @var \Packetery\Checkout\Model\Config\Source\MethodSelect */
    private $methodSelect;

    /** @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory */
    private $orderCollectionFactory;

    /** @var \Packetery\Checkout\Model\Carrier\Facade */
    private $carrierFacade;

    /**
     * Country constructor.
     *
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context
     * @param \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Packetery\Checkout\Model\Config\Source\MethodSelect $methodSelect,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Packetery\Checkout\Model\Carrier\Facade $carrierFacade,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->methodSelect = $methodSelect;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->carrierFacade = $carrierFacade;
    }

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $orderNumber = $item['order_number'];
                $collection = $this->orderCollectionFactory->create();
                /** @var \Magento\Sales\Model\Order $item */
                $order = $collection->getItemByColumnValue('increment_id', $orderNumber);
                $shippingMethod = $order->getShippingMethod(true);
                $shippingAddress = $order->getShippingAddress();
                $carrierCode = $shippingMethod->getData('carrier_code');
                $methodCode = MethodCode::fromString($shippingMethod->getData('method'));
                $carrier = $this->carrierFacade->createHybridCarrier($carrierCode, $methodCode->getDynamicCarrierId(), $methodCode->getMethod(), $shippingAddress->getCountryId());

                $branchName = (string)$item['point_name'];
                $branchId = $item['point_id'];
                $methodContent = $this->methodSelect->getLabelByValue($methodCode->getMethod());
                if ($branchId && $methodCode->getMethod() === Methods::PICKUP_POINT_DELIVERY) {
                    $methodContent = sprintf("%s (%s)", $branchName, $branchId);
                }

                $item[$this->getData('name')] = new ComboPhrase(
                    [
                        $carrier->getFinalCarrierName(),
                        ' - ',
                        $methodContent,
                    ]
                );
            }
        }

        return $dataSource;
    }

    protected function applySorting() {
        // no DB select sorting
    }
}
