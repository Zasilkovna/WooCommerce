<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\Order\Listing;

use Magento\Framework\DB\Sql\Expression;

class SearchResult extends \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult
{
    protected function _initSelect() {
        $packeteryOrderTable = $this->getTable('packetery_order');
        $orderTable = $this->getTable('sales_order');

        $this->getSelect()
            ->from(
                [
                    'main_table' => new Expression(
                        "(
                             SELECT
                                 main_table.order_number AS order_number_reference,
                                 CONCAT_WS('', main_table.recipient_firstname, ' ',main_table.recipient_lastname) AS recipient_fullname,
                                 CONCAT_WS('', main_table.recipient_street, ' ', main_table.recipient_house_number, ' ', main_table.recipient_city, ' ', main_table.recipient_zip) AS recipient_address,
                                 CONCAT_WS('', main_table.point_name, ' ', main_table.point_id) AS delivery_destination,
                                 main_table.value AS value_transformed,
                                 IF(main_table.cod > 0, 1, 0) AS cod_transformed,
                                 main_table.exported AS exported_transformed,
                                 main_table.exported_at AS exported_at_transformed,
                                 sales_order.status AS order_status,
                                 main_table.*
                             FROM {$packeteryOrderTable} AS main_table
                             LEFT JOIN {$orderTable} AS sales_order ON sales_order.increment_id = main_table.order_number
                        )"
                    ),
                ]
            );

        return $this;
    }
}
