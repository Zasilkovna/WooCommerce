<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\ResourceModel;

class PricingruleRepository
{
    /** @var \Packetery\Checkout\Model\ResourceModel\Pricingrule\CollectionFactory  */
    private $pricingRuleCollectionFactory;

    /** @var \Packetery\Checkout\Model\PricingruleFactory */
    private $pricingruleFactory;

    /** @var \Packetery\Checkout\Model\ResourceModel\Weightrule\CollectionFactory  */
    private $weightRuleCollectionFactory;

    /** @var \Packetery\Checkout\Model\WeightruleFactory */
    private $weightruleFactory;

    /** @var \Packetery\Checkout\Model\Carrier\PacketeryConfig */
    private $packeteryConfig;

    /**
     * PricingruleRepository constructor.
     *
     * @param \Packetery\Checkout\Model\ResourceModel\Pricingrule\CollectionFactory $pricingRuleCollectionFactory
     * @param \Packetery\Checkout\Model\PricingruleFactory $pricingruleFactory
     * @param \Packetery\Checkout\Model\ResourceModel\Weightrule\CollectionFactory $weightRuleCollectionFactory
     * @param \Packetery\Checkout\Model\WeightruleFactory $weightruleFactory
     * @param \Packetery\Checkout\Model\Carrier\PacketeryConfig $packeteryConfig
     */
    public function __construct(Pricingrule\CollectionFactory $pricingRuleCollectionFactory, \Packetery\Checkout\Model\PricingruleFactory $pricingruleFactory, Weightrule\CollectionFactory $weightRuleCollectionFactory, \Packetery\Checkout\Model\WeightruleFactory $weightruleFactory, \Packetery\Checkout\Model\Carrier\PacketeryConfig $packeteryConfig)
    {
        $this->pricingRuleCollectionFactory = $pricingRuleCollectionFactory;
        $this->pricingruleFactory = $pricingruleFactory;
        $this->weightRuleCollectionFactory = $weightRuleCollectionFactory;
        $this->weightruleFactory = $weightruleFactory;
        $this->packeteryConfig = $packeteryConfig;
    }

    /**
     * @param array $postData
     * @return bool
     */
    public function validateDuplicateCountry(array $postData): bool
    {
        /** @var \Packetery\Checkout\Model\ResourceModel\Pricingrule\Collection $validation */
        $validation = $this->pricingRuleCollectionFactory->create();
        $validation->addFilter('country_id', $postData['country_id']);
        $validation->addFilter('method', $postData['method']);

        if (isset($postData['id'])) {
            $validation->addFieldToFilter('id', ['nin' => [$postData['id']]]);
        }

        if ($validation->count() > 0) {
            return false;
        }

        return true;
    }

    /**
     * @param array $weightRules as assoc array
     * @return bool
     */
    public function validatePricingRuleMaxWeight(array $weightRules): bool
    {
        $globalMaxWeight = $this->packeteryConfig->getMaxWeight();
        if (!is_numeric($globalMaxWeight)) {
            return false;
        }

        $usedWeights = [];
        foreach ($weightRules as $weightRule) {
            $weight = $weightRule['max_weight'];
            $key = (is_numeric($weight) ? number_format((float)$weight, 4, '.', '') : null);

            if (isset($usedWeights[$key])) {
                return false;
            }

            $usedWeights[$key] = 1;

            if (empty($weight)) {
                continue;
            }

            if ($weight > $globalMaxWeight) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $postData
     * @param array $weightRules
     * @return \Packetery\Checkout\Model\Pricingrule
     * @throws \Packetery\Checkout\Model\Exception\DuplicateCountry
     * @throws \Packetery\Checkout\Model\Exception\InvalidMaxWeight
     * @throws \Packetery\Checkout\Model\Exception\PricingRuleNotFound
     * @throws \Packetery\Checkout\Model\Exception\WeightRuleMissing
     */
    public function savePricingRule(array $postData, array $weightRules): \Packetery\Checkout\Model\Pricingrule
    {
        if (!$this->validateDuplicateCountry($postData)) {
            throw new \Packetery\Checkout\Model\Exception\DuplicateCountry();
        }

        if (!$this->validatePricingRuleMaxWeight($weightRules)) {
            throw new \Packetery\Checkout\Model\Exception\InvalidMaxWeight();
        }

        if (empty($weightRules)) {
            throw new \Packetery\Checkout\Model\Exception\WeightRuleMissing();
        }

        /** @var \Packetery\Checkout\Model\Pricingrule|null $item */
        $item = null;

        /** @var \Packetery\Checkout\Model\ResourceModel\Pricingrule\Collection $collection */
        $collection = $this->pricingRuleCollectionFactory->create();

        if (isset($postData['id'])) {
            $collection->addFilter('id', $postData['id']);
            $collection->setDataToAll($postData);
            $item = $collection->getFirstRecord();

            if ($item === null) {
                throw new \Packetery\Checkout\Model\Exception\PricingRuleNotFound();
            }
        } else {
            /** @var \Packetery\Checkout\Model\Pricingrule $item */
            $item = $this->pricingruleFactory->create();
            $item->setData($postData);
            $collection->addItem($item);
        }

        $collection->save(); // pricing rule must exist before weight rule

        $affectedIds = [];
        foreach ($weightRules as $weightRule) {
            /** @var \Packetery\Checkout\Model\ResourceModel\Weightrule\Collection $weightRuleCollection */
            $weightRuleCollection = $this->weightRuleCollectionFactory->create();

            if (!is_numeric($weightRule['max_weight'])) {
                $weightRule['max_weight'] = null;
            }

            if (isset($weightRule['id'])) {
                $weightRuleCollection->addFilter('packetery_pricing_rule_id', $item->getId());
                $weightRuleCollection->addFilter('id', $weightRule['id']);
                $weightRuleCollection->setDataToAll($weightRule);
                $weightRuleCollection->save();
                $affectedIds[] = $weightRule['id'];
            } else {
                $weightRule['packetery_pricing_rule_id'] = $item->getId();

                /** @var \Packetery\Checkout\Model\Weightrule $weightRuleEntity */
                $weightRuleEntity = $this->weightruleFactory->create();
                $weightRuleEntity->setData($weightRule);
                $weightRuleCollection->addItem($weightRuleEntity);
                $weightRuleCollection->save();
                $affectedIds[] = $weightRuleEntity->getId();
            }
        }

        // weight rule deletion support
        /** @var \Packetery\Checkout\Model\ResourceModel\Weightrule\Collection $weightRuleForDeletion */
        $weightRuleForDeletion = $this->weightRuleCollectionFactory->create();
        $weightRuleForDeletion->addFilter('packetery_pricing_rule_id', $item->getId());
        $weightRuleForDeletion->addFieldToFilter('id', ['nin' => $affectedIds]); // nin = NOT IN (...)

        foreach ($weightRuleForDeletion->getItems() as $itemForDeletion) {
            $itemForDeletion->delete();
        }

        return $item;
    }
}
