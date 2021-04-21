<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Pricing;

use Magento\Shipping\Model\Rate\Result;
use Packetery\Checkout\Model\Carrier\Config\AllowedMethods;
use Packetery\Checkout\Model\Pricingrule;

/**
 * Do not inject PacketeryConfig or Carrier\Packetery due to dependency circulation
 */
class Service
{
    /** @var \Packetery\Checkout\Model\ResourceModel\Pricingrule\CollectionFactory  */
    private $pricingRuleCollectionFactory;

    /** @var \Packetery\Checkout\Model\PricingruleFactory */
    private $pricingruleFactory;

    /** @var \Packetery\Checkout\Model\ResourceModel\Weightrule\CollectionFactory  */
    private $weightRuleCollectionFactory;

    /** @var \Packetery\Checkout\Model\WeightruleFactory */
    private $weightruleFactory;

    /** @var \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory  */
    private $rateMethodFactory;

    /** @var \Magento\Shipping\Model\Rate\ResultFactory  */
    private $rateResultFactory;

    /** @var \Packetery\Checkout\Model\Config\Source\MethodSelect */
    private $methodSelect;

    /**
     * Service constructor.
     *
     * @param \Packetery\Checkout\Model\ResourceModel\Pricingrule\CollectionFactory $pricingRuleCollectionFactory
     * @param \Packetery\Checkout\Model\PricingruleFactory $pricingruleFactory
     * @param \Packetery\Checkout\Model\ResourceModel\Weightrule\CollectionFactory $weightRuleCollectionFactory
     * @param \Packetery\Checkout\Model\WeightruleFactory $weightruleFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Packetery\Checkout\Model\Config\Source\MethodSelect $methodSelect
     */
    public function __construct
    (
        \Packetery\Checkout\Model\ResourceModel\Pricingrule\CollectionFactory $pricingRuleCollectionFactory,
        \Packetery\Checkout\Model\PricingruleFactory $pricingruleFactory,
        \Packetery\Checkout\Model\ResourceModel\Weightrule\CollectionFactory $weightRuleCollectionFactory,
        \Packetery\Checkout\Model\WeightruleFactory $weightruleFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Packetery\Checkout\Model\Config\Source\MethodSelect $methodSelect
    ) {
        $this->pricingRuleCollectionFactory = $pricingRuleCollectionFactory;
        $this->pricingruleFactory = $pricingruleFactory;
        $this->weightRuleCollectionFactory = $weightRuleCollectionFactory;
        $this->weightruleFactory = $weightruleFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->rateResultFactory = $rateResultFactory;
        $this->methodSelect = $methodSelect;
    }

    /**
     * @param \Packetery\Checkout\Model\Pricing\Request $pricingRequest
     * @return \Magento\Shipping\Model\Rate\Result|null
     */
    public function collectRates(Request $pricingRequest): ?Result
    {
        $request = $pricingRequest->getRateRequest();
        $result = $this->rateResultFactory->create();
        $allowedMethods = $pricingRequest->getCarrierConfig()->getAllowedMethods();

        if ($this->hasValidWeight($pricingRequest) === false) {
            return null;
        }

        $methods = $allowedMethods->toArray();
        if (empty($methods)) {
            $methods = $this->methodSelect->getMethods();
        }

        foreach ($methods as $allowedMethod) {
            if ($allowedMethod !== AllowedMethods::PICKUP_POINT_DELIVERY) {
                $branchId = $this->resolvePointId($allowedMethod, $request->getDestCountryId());
                if ($branchId === null) {
                    continue;
                }
            }

            $pricingRule = $this->resolvePricingRule($allowedMethod, $pricingRequest);
            $price = $this->resolvePrice($pricingRequest, $pricingRule);
            $method = $this->createRateMethod($allowedMethod, $pricingRequest, $price);
            $result->append($method);
        }

        return $result;
    }

    /**
     * @param \Packetery\Checkout\Model\Pricing\Request $pricingRequest
     * @return bool
     */
    private function hasValidWeight(Request $pricingRequest): bool
    {
        $weightTotal = $pricingRequest->getRateRequest()->getPackageWeight(); // custom unit
        $weightMax = $pricingRequest->getCarrierConfig()->getMaxWeight();
        return is_numeric($weightMax) && $weightTotal <= $weightMax;
    }

    /** Returns data that are used to figure out destination point id
     * @return int[]
     */
    private function getResolvableDestinationData(): array
    {
        return [
            AllowedMethods::ADDRESS_DELIVERY => [
                'countryBranchIds' => [
                    'CZ' => 106,
                    'SK' => 131,
                    'HU' => 4159,
                    'RO' => 4161,
                    'PL' => 4162,
                ]
            ]
        ];
    }

    /**
     * @param string $countryId
     * @return int|null
     */
    public function resolvePointId(string $method, string $countryId): ?int
    {
        $data = $this->getResolvableDestinationData();
        return ($data[$method]['countryBranchIds'][$countryId] ?? null);
    }

    /**
     * @param string $method
     * @param \Packetery\Checkout\Model\Pricing\Request $pricingRequest
     * @return \Packetery\Checkout\Model\Pricingrule|null
     */
    public function resolvePricingRule(string $method, Request $pricingRequest): ?Pricingrule
    {
        $destCountryId = ($pricingRequest->getRateRequest()->getDestCountryId() ?: null);

        $pricingRuleCollection = $this->pricingRuleCollectionFactory->create();
        $pricingRuleCollection->addFilter('method', $method);
        $pricingRuleCollection->addFilter('country_id', $destCountryId); // iso 2
        $first = ($pricingRuleCollection->getFirstRecord() ?: null);

        return $first;
    }

    /**
     * @param \Packetery\Checkout\Model\Pricing\Request $pricingRequest
     * @param \Packetery\Checkout\Model\Pricingrule|null $pricingRule
     * @return float
     */
    protected function resolvePrice(Request $pricingRequest, ?Pricingrule $pricingRule): float
    {
        $result = null;
        $request = $pricingRequest->getRateRequest();
        $weightTotal = (float)$request->getPackageWeight();
        $priceTotal = (float)$request->getPackageValue();

        $freeShipping = $this->getFreeShippingThreshold($pricingRule, $pricingRequest->getCarrierConfig()->getFreeShippingThreshold());

        if ($freeShipping !== null && $freeShipping <= $priceTotal) {
            return 0;
        }

        $weightRules = null;
        if ($pricingRule) {
            $weightRules = $this->getWeightRulesByPricingRule($pricingRule);
        }

        if (!empty($weightRules)) {
            $result = $this->resolveWeightedPrice($weightRules, $weightTotal, $pricingRequest->getCarrierConfig()->getMaxWeight());
        }

        if ($result === null) {
            $result = $pricingRequest->getCarrierConfig()->getDefaultPrice();
        }

        return $result;
    }

    /**
     * @param \Packetery\Checkout\Model\Weightrule[] $weightRules
     * @param float $weightTotal
     * @return float|null
     */
    protected function resolveWeightedPrice(array $weightRules, float $weightTotal, ?float $fallbackWeight = null): ?float
    {
        $minWeight = null;
        $price = null;

        foreach ($weightRules as $rule) {
            $ruleMaxWeight = $rule->getMaxWeight();
            $rulePrice = $rule->getPrice();

            if ($ruleMaxWeight === null) {
                $ruleMaxWeight = $fallbackWeight;
            }

            $relevant = $weightTotal <= $ruleMaxWeight;
            if ($relevant === false) {
                continue;
            }

            if ($minWeight === null || $minWeight > $ruleMaxWeight) {
                $minWeight = $ruleMaxWeight;
                $price = $rulePrice;
            }
        }

        return $price;
    }

    /**
     * @param string $packeteryMethod
     * @param \Packetery\Checkout\Model\Pricing\Request $request
     * @param float $price
     * @return \Magento\Quote\Model\Quote\Address\RateResult\Method
     */
    protected function createRateMethod(string $packeteryMethod, Request $request, float $price): \Magento\Quote\Model\Quote\Address\RateResult\Method
    {
        $method = $this->rateMethodFactory->create();
        $method->setCarrier($request->getCarrierCode());

        if (empty($method->getCarrierTitle())) {
            $method->setCarrierTitle($request->getCarrierConfig()->getTitle());
        }

        $method->setMethod($packeteryMethod);

        if (empty($method->getMethodTitle())) {
            $method->setMethodTitle($this->methodSelect->getLabelByValue($packeteryMethod));
        }

        $method->setCost($price);
        $method->setPrice($price);

        return $method;
    }

    /**
     * @param \Packetery\Checkout\Model\Pricingrule $pricingRule
     * @return array
     */
    protected function getWeightRulesByPricingRule(Pricingrule $pricingRule): array
    {
        $collection = $this->weightRuleCollectionFactory->create();
        $collection->addFilter('packetery_pricing_rule_id', $pricingRule->getId());
        return $collection->getItems();
    }

    /**
     * @param \Packetery\Checkout\Model\Pricingrule|null $pricingrule
     * @param float|null $globalFreeShipping
     * @return float|null
     */
    protected function getFreeShippingThreshold(?Pricingrule $pricingrule, ?float $globalFreeShipping): ?float
    {
        $countryFreeShipping = ($pricingrule ? $pricingrule->getFreeShipment() : null);

        if (is_numeric($countryFreeShipping)) {
            $freeShipping = $countryFreeShipping;
        } elseif (is_numeric($globalFreeShipping)) {
            $freeShipping = $globalFreeShipping;
        } else {
            $freeShipping = null; // disabled
        }

        return ($freeShipping === null ? null : (float)$freeShipping);
    }
}
