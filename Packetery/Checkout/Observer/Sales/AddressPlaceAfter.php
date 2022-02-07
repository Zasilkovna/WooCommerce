<?php

namespace Packetery\Checkout\Observer\Sales;

class AddressPlaceAfter implements \Magento\Framework\Event\ObserverInterface
{
    /** @var \Magento\Sales\Api\OrderRepositoryInterface */
    protected $orderRepository;

    /** @var \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory */
    private $orderCollectionFactory;

    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderCollectionFactory = $orderCollectionFactory;
    }

    /**
     * Observer is triggered if order address is changed (from admin).
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return void
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    )
    {
        $orderId = $observer->getData('order_id');
        $order = $this->orderRepository->get($orderId);
        $orderNumber = $order->getIncrementId();
        $shippingAddress = $order->getShippingAddress();
        $packeteryAddress = \Packetery\Checkout\Model\Address::fromShippingAddress($shippingAddress);

        $data = [
            'address_validated' => false,
            'recipient_street' => $packeteryAddress->getStreet(),
            'recipient_house_number' => $packeteryAddress->getHouseNumber(),
            'recipient_city' => $packeteryAddress->getCity(),
            'recipient_zip' => $packeteryAddress->getZip(),
            'recipient_county' => $packeteryAddress->getCounty(),
            'recipient_country_id' => $packeteryAddress->getCountryId(),
            'recipient_longitude' => $packeteryAddress->getLongitude(),
            'recipient_latitude' => $packeteryAddress->getLatitude(),
            'recipient_firstname' => $shippingAddress->getData('firstname'),
            'recipient_lastname' => $shippingAddress->getData('lastname'),
            'recipient_company' => $shippingAddress->getData('company'),
            'recipient_email' => $shippingAddress->getData('email'),
            'recipient_phone' => $shippingAddress->getData('telephone'),
        ];

        $orderCollection = $this->orderCollectionFactory->create();
        $orderCollection->addFilter('order_number', $orderNumber);
        $orderCollection->setDataToAll($data);
        $orderCollection->save();
    }
}
