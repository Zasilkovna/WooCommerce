<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\CarrierCountry\Listing;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\DB\Sql\UnionExpression;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Psr\Log\LoggerInterface as Logger;

class SearchResult extends \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult
{
    /** @var \Packetery\Checkout\Ui\Component\CarrierCountry\Form\Modifier */
    private $modifier;

    /** @var \Packetery\Checkout\Model\Carrier\Facade */
    private $carrierFacade;

    /**
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Packetery\Checkout\Ui\Component\CarrierCountry\Form\Modifier $modifier
     * @param \Packetery\Checkout\Model\Carrier\Facade $carrierFacade
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
        \Packetery\Checkout\Ui\Component\CarrierCountry\Form\Modifier $modifier,
        \Packetery\Checkout\Model\Carrier\Facade $carrierFacade,
        $mainTable,
        $resourceModel = null,
        $identifierName = null,
        $connectionName = null
    ) {
        $this->carrierFacade = $carrierFacade;
        $this->modifier = $modifier;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel, $identifierName, $connectionName);
    }

    protected function _initSelect() {
        $neededCountries = $this->carrierFacade->getAllAvailableCountries();
        $assembledQueries = [];

        foreach ($neededCountries as $neededCountry) {
            $options = $this->modifier->getCarriers($neededCountry);
            if (empty($options)) {
                continue; // do not show country if no carriers are available for user configuration
            }

            $items = $this->modifier->getPricingRulesForCountry($neededCountry, true);
            $assembledQueries[] = " SELECT {$this->getConnection()->quote($neededCountry)} AS {$this->getConnection()->quoteIdentifier('country')}, {$this->getConnection()->quote(empty($items) ? 0 : 1)} AS {$this->getConnection()->quoteIdentifier('available')} ";
        }

        $this->getSelect()
            ->from(['main_table' => new UnionExpression($assembledQueries, $this->getSelect()::SQL_UNION, '(%s)')])
            ->reset('columns')
            ->columns(
                [
                    'country',
                    'available',
                ]
            )
            ->group(
                [
                    'country',
                ]
            );

        $this->addFilterToMap('availableName', 'available');

        return $this;
    }
}
