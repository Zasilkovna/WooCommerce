<?php

declare(strict_types=1);

namespace Packetery\Checkout\Block\Adminhtml\Order\Renderer;

use Magento\Backend\Block\Context;
use Magento\Framework\DataObject;
use Packetery\Checkout\Model\Carrier\MethodCode;
use Packetery\Checkout\Model\Carrier\Methods;
use Packetery\Checkout\Model\Misc\ComboPhrase;

class DeliveryDestination extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /** @var \Packetery\Checkout\Model\Config\Source\MethodSelect */
    private $methodSelect;

    /** @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory */
    private $orderCollectionFactory;

    /** @var \Packetery\Checkout\Model\Carrier\Facade */
    private $carrierFacade;

    /**
     * @param Context $context
     * @param \Packetery\Checkout\Model\Config\Source\MethodSelect $methodSelect
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Packetery\Checkout\Model\Carrier\Facade $carrierFacade
     * @param array $data
     */
    public function __construct(
        Context $context,
        \Packetery\Checkout\Model\Config\Source\MethodSelect $methodSelect,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Packetery\Checkout\Model\Carrier\Facade $carrierFacade,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->methodSelect = $methodSelect;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->carrierFacade = $carrierFacade;
    }

    /**
     * render address
     *
     * @param DataObject $row
     * @return string
     */
    public function render(DataObject $row) {
        $orderNumber = $row->getData('order_number');
        $collection = $this->orderCollectionFactory->create();
        /** @var \Magento\Sales\Model\Order $item */
        $item = $collection->getItemByColumnValue('increment_id', $orderNumber);
        $shippingMethod = $item->getShippingMethod(true);
        $shippingAddress = $item->getShippingAddress();
        $carrierCode = $shippingMethod->getData('carrier_code');
        $methodCode = MethodCode::fromString($shippingMethod->getData('method'));
        $carrier = $this->carrierFacade->createHybridCarrier($carrierCode, $methodCode->getDynamicCarrierId(), $methodCode->getMethod(), $shippingAddress->getCountryId());

        $branchName = (string)$row->getData('point_name');
        $branchId = $row->getData('point_id');
        $methodContent = $this->methodSelect->getLabelByValue($methodCode->getMethod());
        if ($branchId && $methodCode->getMethod() === Methods::PICKUP_POINT_DELIVERY) {
            $methodContent = sprintf("%s (%s)", $branchName, $branchId);
        }

        return new ComboPhrase(
            [
                $carrier->getFinalCarrierName(),
                ' - ',
                $methodContent,
            ]
        );
    }
}
