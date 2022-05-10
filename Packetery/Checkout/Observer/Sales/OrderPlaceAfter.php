<?php

namespace Packetery\Checkout\Observer\Sales;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Packetery\Checkout\Model\Address;
use Packetery\Checkout\Model\AddressValidationSelect;
use Packetery\Checkout\Model\Carrier\Methods;
use Packetery\Checkout\Model\Carrier\ShippingRateCode;
use Packetery\Checkout\Model\Payment\MethodList;

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

    /** @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory */
    private $magentoOrderCollectionFactory;

    /** @var \Magento\Shipping\Model\CarrierFactory */
    private $carrierFactory;

    /** @var \Packetery\Checkout\Model\Weight\Calculator */
    private $weightCalculator;

    /** @var \Packetery\Checkout\Model\Pricing\Service */
    private $pricingService;

    /** @var \Magento\Sales\Model\Order\AddressRepository */
    private $orderAddressRepository;

    /** @var PriceCurrencyInterface */
    private $priceCurrency;

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
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $magentoOrderCollectionFactory
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Packetery\Checkout\Model\Carrier\Imp\Packetery\Carrier $packetery,
        \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Shipping\Model\CarrierFactory $carrierFactory,
        \Packetery\Checkout\Model\Weight\Calculator $weightCalculator,
        \Packetery\Checkout\Model\Pricing\Service $pricingService,
        \Magento\Sales\Model\Order\AddressRepository $orderAddressRepository,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $magentoOrderCollectionFactory
    ) {
        $this->storeManager = $storeManager;
        $this->checkoutSession = $checkoutSession;
        $this->packeteryConfig = $packetery->getPacketeryConfig();
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->carrierFactory = $carrierFactory;
        $this->weightCalculator = $weightCalculator;
        $this->pricingService = $pricingService;
        $this->orderAddressRepository = $orderAddressRepository;
        $this->priceCurrency = $priceCurrency;
        $this->magentoOrderCollectionFactory = $magentoOrderCollectionFactory;
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
        if (ShippingRateCode::isPacketery($order->getShippingMethod()) === false) {
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
        $paymentMethod = $order->getPayment()->getMethod();
        $isCOD = $this->isCod($paymentMethod);
        $shippingRate = ShippingRateCode::fromString($order->getShippingMethod());
        $deliveryMethod = $shippingRate->getMethodCode();

        $relatedPricingRule = $this->pricingService->resolvePricingRule(
            $deliveryMethod->getMethod(),
            $destinationAddress->getCountryId(),
            $shippingRate->getCarrierCode(),
            $deliveryMethod->getDynamicCarrierId()
        );

        if ($relatedPricingRule === null) {
            throw new InputException(__('Pricing rule was not found. Please choose delivery method.'));
        }

        if ($isCOD && MethodList::exceedsValueMaxLimit($order->getGrandTotal(), $relatedPricingRule->getMaxCOD())) {
            throw new InputException(__('Selected payment method is not allowed because the grand total exceeds the max COD (%1) set up for this carrier.', $this->priceCurrency->format($relatedPricingRule->getMaxCOD(), false)));
        }

        if ($postData)
        {
            // new order from frontend

            if ($deliveryMethod->getMethod() === Methods::PICKUP_POINT_DELIVERY) {
                // pickup point delivery
                $point = $postData->packetery->point;
                $pointId = $point->pointId;
                $pointName = $point->name;
                $isCarrier = (bool)$point->carrierId;
                $carrierPickupPoint = ($point->carrierPickupPointId ?: null);
            } else {
                $validatedAddress = $postData->packetery->validatedAddress;
                if (!$validatedAddress && $relatedPricingRule->getAddressValidation() === AddressValidationSelect::REQUIRED) {
                    throw new InputException(__('Please select address via Packeta widget'));
                }

                if ($validatedAddress) {
                    $destinationAddress = Address::fromValidatedAddress($validatedAddress);
                    $addressValidated = true;
                }

                $pointId = $this->resolvePointId($shippingRate, $destinationAddress);
                $pointName = '';
            }
        }
        else
        {
            // creating order from admin

            $packeteryOrderData = $this->getOriginalPacketeryOrderData($order);
            if (!empty($packeteryOrderData)) {
                $pointId = $packeteryOrderData['point_id'];
                $pointName = $packeteryOrderData['point_name'];
                $isCarrier = (bool)$packeteryOrderData['is_carrier'];
                $carrierPickupPoint = $packeteryOrderData['carrier_pickup_point'];
            }

            if (empty($packeteryOrderData) && $deliveryMethod->getMethod() === Methods::PICKUP_POINT_DELIVERY) {
                $pointId = -1;
                $pointName = '';
                $isCarrier = false;
                $carrierPickupPoint = null;
            }

            if(empty($packeteryOrderData) && $deliveryMethod->getMethod() !== Methods::PICKUP_POINT_DELIVERY) {
                $pointId = $this->resolvePointId($shippingRate, $destinationAddress);
                $pointName = '';
            }
        }

        if (empty($pointId)) {
            throw new InputException(__('You must select pick-up point'));
        }

        $data = [
            'order_number' => $order->getIncrementId(),
            'recipient_firstname' => $magentoShippingAddress->getFirstname(),
            'recipient_lastname' => $magentoShippingAddress->getLastname(),
            'recipient_company' => $magentoShippingAddress->getCompany(),
            'recipient_email' => $magentoShippingAddress->getEmail(),
            'recipient_phone' => $magentoShippingAddress->getTelephone(),
            'cod' => ($isCOD ? $order->getGrandTotal() : 0),
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

    private function resolvePointId(ShippingRateCode $shippingRate, Address $destinationAddress) {
        $deliveryMethod = $shippingRate->getMethodCode();

        /** @var \Packetery\Checkout\Model\Carrier\AbstractCarrier $carrier */
        $carrier = $this->carrierFactory->create($shippingRate->getCarrierCode());
        return $carrier->getPacketeryBrain()->resolvePointId(
            $deliveryMethod->getMethod(),
            $destinationAddress->getCountryId(),
            $carrier->getPacketeryBrain()->getDynamicCarrierById($deliveryMethod->getDynamicCarrierId())
        );
    }

    private function getOriginalPacketeryOrderData(\Magento\Sales\Model\Order $order) {
        $orderIdOriginal = self::getRealOrderId($order->getIncrementId());
        if (!is_numeric($orderIdOriginal))
        {
            return null;
        }

        $magentoOrderCollection = $this->magentoOrderCollectionFactory->create();
        $magentoOrderCollection->addFilter('increment_id', $orderIdOriginal);
        $magentoOrderCollection->load();
        /** @var \Magento\Sales\Model\Order $magentoOrder */
        $magentoOrder = $magentoOrderCollection->fetchItem();

        if (!$magentoOrder || $order->getShippingMethod() !== $magentoOrder->getShippingMethod()) {
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
