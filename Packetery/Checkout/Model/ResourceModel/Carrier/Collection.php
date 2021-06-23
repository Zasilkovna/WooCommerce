<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\ResourceModel\Carrier;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';

    protected $_eventPrefix = 'packetery_checkout_carrier_collection';

    protected $_eventObject = 'carrier_collection';

    /**
     * @return void
     */
    protected function _construct() {
        $this->_init('Packetery\Checkout\Model\Carrier', 'Packetery\Checkout\Model\ResourceModel\Carrier');
    }

    /**
     * @return \Packetery\Checkout\Model\Carrier[]
     */
    public function getItems() {
        return parent::getItems();
    }

    /**
     *  For frontend checkout
     */
    public function resolvableOnly(): void {
        $this->whereDeleted(false);
        $this->supportedOnly();
    }

    /**
     * For admin configuration page
     */
    public function configurableOnly(): void {
        $this->whereDeleted(false);
        $this->supportedOnly();
    }

    /**
     * @param bool $value
     */
    public function whereDeleted(bool $value): void {
        $this->addFilter('main_table.deleted', $value);
    }

    /**
     * @param int[] $excludeCarrierIds
     */
    public function whereCarrierIdNotIn(array $excludeCarrierIds): void {
        $this->addFieldToFilter('main_table.carrier_id', ['nin' => $excludeCarrierIds]);
    }

    /**
     * dynamic carriers with attributes not supported by Packetery extension are omitted
     */
    private function supportedOnly(): void {
        $this->addFieldToFilter('main_table.carrier_id', ['nin' => [257, 136, 134, 132]]); // večerní doručení todo implement ZIP code logic
        $this->addFilter('main_table.disallows_cod', 0); // todo implement payment method filter
        $this->addFilter('main_table.customs_declarations', 0); // todo what does it require? New order edit form fields?
    }

    /**
     * @param string $country
     */
    public function whereCountry(string $country): void {
        $this->addFilter('main_table.country', $country);
    }

    /**
     * @param string $method
     */
    public function forDeliveryMethod(string $method): void {
        $this->forDeliveryMethods([$method]);
    }

    /**
     * @param array $methods
     */
    public function forDeliveryMethods(array $methods): void {
        $isPickupPointsValues = [];

        if (in_array(\Packetery\Checkout\Model\Carrier\Methods::DIRECT_ADDRESS_DELIVERY, $methods)) {
            $isPickupPointsValues[] = 0;
        }

        if (in_array(\Packetery\Checkout\Model\Carrier\Methods::PICKUP_POINT_DELIVERY, $methods)) {
            $isPickupPointsValues[] = 1;
        }

        if (!empty($isPickupPointsValues)) {
            $this->addFieldToFilter('main_table.is_pickup_points', [
                'in' => $isPickupPointsValues
            ]);
            return;
        }

        $this->getSelect()->where('0'); // no results will be returns
    }
}
