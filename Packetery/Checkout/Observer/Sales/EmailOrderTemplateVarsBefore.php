<?php

declare(strict_types=1);

namespace Packetery\Checkout\Observer\Sales;

use Packetery\Checkout\Model\Carrier\MethodCode;
use Packetery\Checkout\Model\Carrier\Methods;

class EmailOrderTemplateVarsBefore implements \Magento\Framework\Event\ObserverInterface
{
    /** @var \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory */
    private $orderCollectionFactory;

    /** @var \Packetery\Checkout\Model\Carrier\Facade */
    private $carrierFacade;

    /**
     * @param \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Packetery\Checkout\Model\Carrier\Facade $carrierFacade
     */
    public function __construct(\Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory, \Packetery\Checkout\Model\Carrier\Facade $carrierFacade) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->carrierFacade = $carrierFacade;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var array $transport */
        $transport = ( $observer->hasData( 'transport' ) ? $observer->getData( 'transport' ) : $observer->getData( 'transportObject' ) );
        /** @var \Magento\Sales\Model\Order $order */
        $order = $transport['order'];
        $collection = $this->orderCollectionFactory->create();
        $collection->addFilter('order_number', $order->getData( 'increment_id' ));
        /** @var \Packetery\Checkout\Model\Order|null $packeteryOrder */
        $items = ( $collection->getItems() ?: [] );
        $packeteryOrder = array_shift($items);

        $transport['packetery_is_pickup_point'] = false;
        $transport['packetery_is_address_delivery'] = false;
        $transport['packetery_point_id'] = '';
        $transport['packetery_point_name'] = '';
        $transport['packetery_carrier_name'] = '';

        if ($packeteryOrder) {
            $rate = $order->getShippingMethod(true);
            $carrierCode = $rate->getData('carrier_code');
            $methodCode = MethodCode::fromString($rate->getData('method'));
            $cache = [];
            $hybridCarrier = $this->carrierFacade->createHybridCarrierCached($cache, $carrierCode, $methodCode->getDynamicCarrierId(), $methodCode->getMethod(), '');

            $transport['packetery_is_pickup_point'] = Methods::PICKUP_POINT_DELIVERY === $methodCode->getMethod();
            $transport['packetery_is_address_delivery'] = Methods::isAnyAddressDelivery($methodCode->getMethod());
            $transport['packetery_point_id'] = $packeteryOrder->getPointId();
            $transport['packetery_point_name'] = $packeteryOrder->getPointName();
            $transport['packetery_carrier_name'] = $hybridCarrier->getFinalCarrierName();
        }
    }
}
