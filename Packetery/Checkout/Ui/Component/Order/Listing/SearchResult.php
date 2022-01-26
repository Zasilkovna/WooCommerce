<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\Order\Listing;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Psr\Log\LoggerInterface as Logger;

class SearchResult extends \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult
{
    /**
     * @var \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * SearchResult constructor.
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param string $mainTable
     * @param null|string $resourceModel
     * @param null|string $identifierName
     * @param null|string $connectionName
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        $mainTable,
        $resourceModel = null,
        $identifierName = null,
        $connectionName = null
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $mainTable,
            $resourceModel,
            $identifierName,
            $connectionName
        );
    }

    protected function _initSelect() {
        $subQuery = $this->orderCollectionFactory->create()->getSelect();
        $subQuery->reset('columns');
        $subQuery->columns(
            [
                'order_number_reference' => 'main_table.order_number',
                'recipient_fullname' => "CONCAT_WS('', main_table.recipient_firstname, ' ',main_table.recipient_lastname)",
                'recipient_address' => "CONCAT_WS('', main_table.recipient_street, ' ', main_table.recipient_house_number, ' ', main_table.recipient_city, ' ', main_table.recipient_zip)",
                'delivery_destination' => "CONCAT_WS('', main_table.point_name, ' ', main_table.point_id)",
                'value_transformed' => "main_table.value",
                'cod_transformed' => "IF(main_table.cod > 0, 1, 0)",
                'exported_transformed' => "main_table.exported",
                'exported_at_transformed' => "main_table.exported_at",
                'order_status' => "sales_order.status",
                'shipping_rate_code' => "sales_order.shipping_method",
                'created_at' => 'sales_order.created_at',
                'main_table.*'
            ]
        );

        $this->getSelect()
            ->from(
                [
                    'main_table' => new Expression('(' . $subQuery->assemble() . ')'),
                ]
            );

        return $this;
    }
}
