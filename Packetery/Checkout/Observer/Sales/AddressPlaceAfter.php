<?php

namespace Packetery\Checkout\Observer\Sales;

use Magento\Checkout\Model\Session as CheckoutSession;

class AddressPlaceAfter implements \Magento\Framework\Event\ObserverInterface
{
    /** @var \Magento\Framework\App\Config */
    private $scopeConfig;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    private $storeManager;

    /** @var \\Magento\Framework\App\ResourceConnection */
    private $resourceConnection;

    /** @var \Magento\Sales\Api\OrderRepositoryInterface */
    protected $orderRepository;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->orderRepository = $orderRepository;
        $this->resourceConnection = $resourceConnection;
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
        $address = $order->getShippingAddress();

        $connection = $this->resourceConnection->getConnection();

        $streetMatches = [];
        $match = preg_match('/^(.*[^0-9]+) (([1-9][0-9]*)\/)?([1-9][0-9]*[a-cA-C]?)$/', $order->getShippingAddress()->getStreet()[0], $streetMatches);

        // street and house number
        if (!$match) {
            $houseNumber = null;
            $street = $order->getShippingAddress()->getStreet()[0];
        } elseif (!isset($streetMatches[4])) {
            $houseNumber = null;
            $street = $streetMatches[1];
        } else {
            $houseNumber = (!empty($streetMatches[3])) ? $streetMatches[3] . "/" . $streetMatches[4] : $streetMatches[4];
            $street = $streetMatches[1];
        }

        // update of packetery order address
        $connection->update('packetery_order', [
                'recipient_phone' => $address->getData('telephone'),
                'recipient_street' => $street,
                'recipient_house_number' => $houseNumber,
                'recipient_city' => $address->getData('city'),
                'recipient_zip' => $address->getData('postcode'),
                'recipient_firstname' => $address->getData('firstname'),
                'recipient_lastname' => $address->getData('lastname'),
                'recipient_company' => $address->getData('company'),
                'recipient_email' => $address->getData('email'),
            ],
            ['order_number = ?' => $orderNumber]);
    }
}
