<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Pricingrule;

use Magento\Ui\DataProvider\AbstractDataProvider;

class DataProvider extends AbstractDataProvider
{
    /** @var \Packetery\Checkout\Model\ResourceModel\Pricingrule\Collection */
    protected $collection;

    /** @var \Packetery\Checkout\Model\ResourceModel\Weightrule\CollectionFactory */
    protected $weightRuleCollectionFactory;

    /**
     * DataProvider constructor.
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param \Packetery\Checkout\Model\ResourceModel\Pricingrule\CollectionFactory $collectionFactory
     * @param \Packetery\Checkout\Model\ResourceModel\Weightrule\CollectionFactory $weightRuleCollectionFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        \Packetery\Checkout\Model\ResourceModel\Pricingrule\CollectionFactory $collectionFactory,
        \Packetery\Checkout\Model\ResourceModel\Weightrule\CollectionFactory $weightRuleCollectionFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
        $this->weightRuleCollectionFactory = $weightRuleCollectionFactory;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        $result = [];
        foreach ($this->collection->getItems() as $item) {
            $result[$item->getId()]['general'] = $item->getData(); // princing rules

            $result[$item->getId()]['general']['weightRules'] = [];
            $result[$item->getId()]['general']['weightRules']['weightRules'] = []; // magento renders data in such structure

            /** @var \Packetery\Checkout\Model\ResourceModel\Weightrule\Collection $weightRuleCollection */
            $weightRuleCollection = $this->weightRuleCollectionFactory->create();
            $weightRuleCollection->addFilter('packetery_pricing_rule_id', $item->getId());
            $weightRules = $weightRuleCollection->getItems();
            foreach ($weightRules as $weightRule) {
                $result[$item->getId()]['general']['weightRules']['weightRules'][] = $weightRule->getData(); // must use natural array keys
            }
        }
        return $result;
    }
}
