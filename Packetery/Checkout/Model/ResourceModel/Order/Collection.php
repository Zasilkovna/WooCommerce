<?php
namespace Packetery\Checkout\Model\ResourceModel\Order;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'packetery_checkout_order_collection';
    protected $_eventObject = 'order_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Packetery\Checkout\Model\Order', 'Packetery\Checkout\Model\ResourceModel\Order');
    }

    /**
     * @return string
     */
    private function createSalesOrderCarrierCodeCondition(): string {
        $carrierCodes = \Packetery\Checkout\Model\Carrier\Facade::getAllCarrierCodes();

        $carrierCodesConditions = [];
        foreach ($carrierCodes as $carrierCode) {
            $carrierCodesConditions[] = "`sales_order`.`shipping_method` LIKE " . $this->getConnection()->quote($carrierCode . '\_%', 'STRING');
        }
        $carrierCodesImploded = implode(' OR ', $carrierCodesConditions);
        if (empty($carrierCodesImploded)) {
            $carrierCodesImploded = '1';
        }

        return $carrierCodesImploded;
    }

    /**
     * Joins Magento sale orders in a way that omits redirected orders from Packeta to foreign carrier.
     */
    public function joinSalesOrder(): void {
        $carrierCodesImploded = $this->createSalesOrderCarrierCodeCondition();
        $this->join(['sales_order' => 'sales_order'],  "`main_table`.`order_number` = `sales_order`.`increment_id` AND ($carrierCodesImploded)", '');
    }
}
