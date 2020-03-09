<?php

namespace Packetery\Checkout\Observer\Sales;

use Magento\Checkout\Model\Session as CheckoutSession;

class OrderPlaceAfter implements \Magento\Framework\Event\ObserverInterface
{
	const SHIPPING_CODE = 'packetery';

    const CZ_FICTIVE_BRANCH = array(
        'id' => 540,
        'name' => 'AA Andělská Hora',
    );
    const SK_FICTIVE_BRANCH = array(
        'id' => 703,
        'name' => 'Abovce',
    );
    const HU_FICTIVE_BRANCH = array(
        'id' => 2485,
        'name' => 'AA Angyalföld',
    );

    /** @var CheckoutSession */
    protected $checkoutSession;

    /** @var \Magento\Framework\App\Config */
    private $scopeConfig;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    private $storeManager;

    /** @var \\Magento\Framework\App\ResourceConnection */
    private $resourceConnection;


    public function __construct(
        CheckoutSession $checkoutSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->checkoutSession = $checkoutSession;
        $this->resourceConnection = $resourceConnection;
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

        $fictiveBranches = array(
            'cz' => self::CZ_FICTIVE_BRANCH,
            'sk' => self::SK_FICTIVE_BRANCH,
            'hu' => self::HU_FICTIVE_BRANCH,
        );

        $order = $observer->getEvent()->getOrder();
        $country = strtolower($order->getShippingAddress()->getCountryId());

        // IF PACKETERY SHIPPING IS NOT SELECTED, RETURN
        if (strpos($order->getShippingMethod(), self::SHIPPING_CODE) === false)
        {
            return;
        }

        // GET DATA
        $streetMatches = [];
        $match = preg_match('/^(.*[^0-9]+) (([1-9][0-9]*)\/)?([1-9][0-9]*[a-cA-C]?)$/', $order->getShippingAddress()->getStreet()[0], $streetMatches);

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

        $weight = 0;
        foreach ($order->getAllItems() as $item)
        {
            $weight += ($item->getWeight() * $item->getQtyOrdered());
        }

        $postData = json_decode(file_get_contents("php://input"));
        $pointId = NULL;
        $pointName = NULL;

        if ($postData && !empty($postData->packetery))
        {
            // new order from frontend
            $pointId = $postData->packetery->id;
            $pointName = $postData->packetery->name;
        }
        else
        {
            // creating/editing order from admin
            $packetery = $this->getRealOrderPacketery($order);
            $pointId = $packetery['point_id'];
            $pointName = $packetery['point_name'];
        }

		$paymentMethod = $order->getPayment()->getMethod();

        $data = [
            'order_number' => $order->getIncrementId(),
            'recipient_firstname' => $order->getShippingAddress()->getFirstname(),
            'recipient_lastname' => $order->getShippingAddress()->getLastname(),
            'recipient_company' => $order->getShippingAddress()->getCompany(),
            'recipient_email' => $order->getShippingAddress()->getEmail(),
            'recipient_phone' => $order->getShippingAddress()->getTelephone(),
            'cod' => ($this->isCod($paymentMethod) ? $order->getGrandTotal() : 0),
            'currency' => $order->getOrderCurrencyCode(),
            'value' => $order->getGrandTotal(),
            'weight' => $weight,
            'point_id' => ($pointId ? $pointId : (isset($fictiveBranches[$country]) ? $fictiveBranches[$country]['id'] : self::CZ_FICTIVE_BRANCH['id'])),
            'point_name' => ($pointId ? $pointName : (isset($fictiveBranches[$country]) ? $fictiveBranches[$country]['name'] : self::CZ_FICTIVE_BRANCH['name'])),
            'sender_label' => $this->getLabel(),
            'recipient_street' => $street,
            'recipient_house_number' => $houseNumber,
            'recipient_city' => $order->getShippingAddress()->getCity(),
            'recipient_zip' => $order->getShippingAddress()->getPostcode(),
            'exported' => 0,
        ];

        $this->saveData($data);

    }

    private function getRealOrderPacketery($order)
    {
        $null = [
            'point_id'   => NULL,
            'point_name' => NULL,
        ];

        $orderIdOriginal = self::getRealOrderId($order->getIncrementId());
        if (!is_numeric($orderIdOriginal))
        {
            return $null;
        }

        $query = "
            SELECT `point_id`, `point_name`
            FROM `packetery_order`
            WHERE `order_number` = :order_number
        ";

        $data = $this->resourceConnection->getConnection()
            ->fetchRow($query, ['order_number' => $orderIdOriginal]);

        if (empty($data))
        {
            return $null;
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
        $codPayments = $this->scopeConfig->getValue(
            'packetery_cod/general/payment_methods',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $codPayments = explode(',', $codPayments);

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
	private function saveData($data)
	{
        $connection= $this->resourceConnection->getConnection();

		$query = "INSERT INTO packetery_order
					(`order_number`, `recipient_firstname`, `recipient_lastname`, `recipient_phone`, `recipient_company`, `recipient_email`, `cod` ,`currency`,`value`, `weight`,`point_id`,`point_name`,`recipient_street`,`recipient_house_number`,`recipient_city`,`recipient_zip`, `sender_label`, `exported`)
					VALUES (:order_number, :recipient_firstname, :recipient_lastname, :recipient_phone, :recipient_company,:recipient_email, :cod, :currency, :value, :weight, :point_id, :point_name, :recipient_street, :recipient_house_number, :recipient_city, :recipient_zip, :sender_label, :exported)";

		$connection->query($query, $data);
	}
}
