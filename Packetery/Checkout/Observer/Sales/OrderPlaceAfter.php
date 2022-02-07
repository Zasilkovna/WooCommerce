<?php

namespace Packetery\Checkout\Observer\Sales;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\InputException;
use Packetery\Checkout\Model\Address;
use Packetery\Checkout\Model\AddressValidationSelect;
use Packetery\Checkout\Model\Carrier\AbstractBrain;
use Packetery\Checkout\Model\Carrier\MethodCode;
use Packetery\Checkout\Model\Carrier\Methods;

class OrderPlaceAfter implements \Magento\Framework\Event\ObserverInterface
{
    /** @var CheckoutSession */
    protected $checkoutSession;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    private $storeManager;

    /** @var \Packetery\Checkout\Model\Carrier\Imp\Packetery\Config */
    private $packeteryConfig;

    /** @var \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory */
    private $orderCollectionFactory;

    /** @var \Magento\Shipping\Model\CarrierFactory */
    private $carrierFactory;

    /** @var \Packetery\Checkout\Model\Weight\Calculator */
    private $weightCalculator;

    /** @var \Packetery\Checkout\Model\Pricing\Service */
    private $pricingService;

    /** @var \Magento\Sales\Model\Order\AddressRepository */
    private $orderAddressRepository;

    /**
     * OrderPlaceAfter constructor.
     *
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Packetery\Checkout\Model\Carrier\Imp\Packetery\Carrier $packetery
     * @param \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\Shipping\Model\CarrierFactory $carrierFactory
     * @param \Packetery\Checkout\Model\Weight\Calculator $weightCalculator
     * @param \Packetery\Checkout\Model\Pricing\Service $pricingService
     * @param \Magento\Sales\Model\Order\AddressRepository $orderAddressRepository
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Packetery\Checkout\Model\Carrier\Imp\Packetery\Carrier $packetery,
        \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Shipping\Model\CarrierFactory $carrierFactory,
        \Packetery\Checkout\Model\Weight\Calculator $weightCalculator,
        \Packetery\Checkout\Model\Pricing\Service $pricingService,
        \Magento\Sales\Model\Order\AddressRepository $orderAddressRepository
    ) {
        $this->storeManager = $storeManager;
        $this->checkoutSession = $checkoutSession;
        $this->packeteryConfig = $packetery->getPacketeryConfig();
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->carrierFactory = $carrierFactory;
        $this->weightCalculator = $weightCalculator;
        $this->pricingService = $pricingService;
        $this->orderAddressRepository = $orderAddressRepository;
    }

    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        // IF PACKETERY SHIPPING IS NOT SELECTED, RETURN
        if (strpos($order->getShippingMethod(), AbstractBrain::PREFIX) === false)
        {
            return;
        }

        $weight = $this->weightCalculator->getOrderWeight($order);

        $postData = json_decode(file_get_contents("php://input"));
        $pointId = NULL;
        $pointName = NULL;
        $point = NULL;
        $isCarrier = false;
        $addressValidated = false;
        $carrierPickupPoint = null;
        $magentoShippingAddress = $order->getShippingAddress();
        $destinationAddress = Address::fromShippingAddress($magentoShippingAddress);

        if ($postData)
        {
            // new order from frontend
            $shippingMethod = $order->getShippingMethod(true);
            $deliveryMethod = MethodCode::fromString($shippingMethod['method']);
            if ($deliveryMethod->getMethod() === Methods::PICKUP_POINT_DELIVERY) {
                // pickup point delivery
                $point = $postData->packetery->point;
                $pointId = $point->pointId;
                $pointName = $point->name;
                $isCarrier = (bool)$point->carrierId;
                $carrierPickupPoint = ($point->carrierPickupPointId ?: null);
            } else {
                $relatedPricingRule = $this->pricingService->resolvePricingRule(
                    $deliveryMethod->getMethod(),
                    $destinationAddress->getCountryId(), // shipping address countryId === validated widget country
                    $shippingMethod['carrier_code'],
                    $deliveryMethod->getDynamicCarrierId()
                );

                if ($relatedPricingRule === null) {
                    throw new InputException(__('Pricing rule was not found. Please choose delivery method.'));
                }

                $validatedAddress = $postData->packetery->validatedAddress;
                if (!$validatedAddress && $relatedPricingRule->getAddressValidation() === AddressValidationSelect::REQUIRED) {
                    throw new InputException(__('Please select address via Packeta widget'));
                }

                if ($validatedAddress) {
                    $destinationAddress = Address::fromValidatedAddress($validatedAddress);
                    $addressValidated = true;
                }

                /** @var \Packetery\Checkout\Model\Carrier\AbstractCarrier $carrier */
                $carrier = $this->carrierFactory->create($shippingMethod['carrier_code']);
                $pointId = $carrier->getPacketeryBrain()->resolvePointId(
                    $deliveryMethod->getMethod(),
                    $destinationAddress->getCountryId(),
                    $carrier->getPacketeryBrain()->getDynamicCarrierById($deliveryMethod->getDynamicCarrierId())
                );

                $pointName = '';
            }
        }
        else
        {
            // creating order from admin
            $packetery = $this->getRealOrderPacketery($order);
            if (!empty($packetery)) {
                $pointId = $packetery['point_id'];
                $pointName = $packetery['point_name'];
                $isCarrier = (bool)$packetery['is_carrier'];
                $carrierPickupPoint = $packetery['carrier_pickup_point'];
            }
        }

        if (empty($pointId)) {
            throw new InputException(__('You must select pick-up point'));
        }

		$paymentMethod = $order->getPayment()->getMethod();

        $data = [
            'order_number' => $order->getIncrementId(),
            'recipient_firstname' => $magentoShippingAddress->getFirstname(),
            'recipient_lastname' => $magentoShippingAddress->getLastname(),
            'recipient_company' => $magentoShippingAddress->getCompany(),
            'recipient_email' => $magentoShippingAddress->getEmail(),
            'recipient_phone' => $magentoShippingAddress->getTelephone(),
            'cod' => ($this->isCod($paymentMethod) ? $order->getGrandTotal() : 0),
            'currency' => $order->getOrderCurrencyCode(),
            'value' => $order->getGrandTotal(),
            'weight' => $weight,
            'point_id' => $pointId,
            'point_name' => $pointName,
            'is_carrier' => $isCarrier,
            'carrier_pickup_point' => $carrierPickupPoint,
            'sender_label' => $this->getLabel(),
            'address_validated' => $addressValidated,
            'recipient_street' => $destinationAddress->getStreet(),
            'recipient_house_number' => $destinationAddress->getHouseNumber(),
            'recipient_city' => $destinationAddress->getCity(),
            'recipient_zip' => $destinationAddress->getZip(),
            'recipient_country_id' => $destinationAddress->getCountryId(),
            'recipient_county' => $destinationAddress->getCounty(),
            'recipient_longitude' => $destinationAddress->getLongitude(),
            'recipient_latitude' => $destinationAddress->getLatitude(),
            'exported' => 0,
        ];

        $this->saveData($data);

        if ($addressValidated) {
            $magentoShippingAddress->setCity($destinationAddress->getCity());
            $magentoShippingAddress->setStreet([$destinationAddress->getStreet(), $destinationAddress->getHouseNumber()]);
            $magentoShippingAddress->setCountryId($destinationAddress->getCountryId());
            $magentoShippingAddress->setPostcode($destinationAddress->getZip());
            $magentoShippingAddress->setRegion($destinationAddress->getCounty());
            $magentoShippingAddress->setRegionCode(null);
            $magentoShippingAddress->setRegionId(null);
            $this->orderAddressRepository->save($magentoShippingAddress);
        }
    }

    private function getRealOrderPacketery($order)
    {
        $orderIdOriginal = self::getRealOrderId($order->getIncrementId());
        if (!is_numeric($orderIdOriginal))
        {
            return null;
        }

        $collection = $this->orderCollectionFactory->create();
        $collection->addFilter('order_number', $orderIdOriginal);
        $collection->load();
        $item = $collection->fetchItem();

        if (empty($item)) {
            return null;
        }

        $data = $item->toArray(['point_id', 'point_name', 'is_carrier', 'carrier_pickup_point']);

        if (empty($data))
        {
            return null;
        }

        return $data;
    }

    private static function getRealOrderId($orderId)
    {
        // $orderId = ltrim($orderId, 0);
        $orderId = strstr($orderId, "-", TRUE);

        return $orderId;
    }

	/**
	 * Check, if it is COD type in Packetery configuration
	 */
	private function isCod($methodCode)
	{
        $codPayments = $this->packeteryConfig->getCodMethods();
		return in_array($methodCode, $codPayments);
	}

	/**
	 * Create unique label/id of the store
	 */
	private function getLabel()
	{
        $store = $this->storeManager->getGroup();

        if($store)
        {
            return $store->getCode();
        }
        return null;
    }

	/**
	 * Save order data to packetery module
	 * @package array $data
	 */
	private function saveData(array $data): void
	{
        /** @var \Packetery\Checkout\Model\ResourceModel\Order\Collection $collection */
        $collection = $this->orderCollectionFactory->create();
        $order = $collection->getNewEmptyItem();
        $order->setData($data);
        $collection->addItem($order);
        $collection->save();
	}
}
