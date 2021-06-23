<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Pricing;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;
use Packetery\Checkout\Model\Carrier\Config\AbstractConfig;
use Packetery\Checkout\Model\Carrier\MethodCode;
use Packetery\Checkout\Model\Pricingrule;
use Packetery\Checkout\Model\Weightrule;

/**
 * Do not inject any Carrier related services due to dependency circulation
 */
class Service
{
    /** @var \Packetery\Checkout\Model\ResourceModel\Pricingrule\CollectionFactory  */
    private $pricingRuleCollectionFactory;

    /** @var \Packetery\Checkout\Model\ResourceModel\Weightrule\CollectionFactory  */
    private $weightRuleCollectionFactory;

    /** @var \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory  */
    private $rateMethodFactory;

    /** @var \Magento\Shipping\Model\Rate\ResultFactory  */
    private $rateResultFactory;

    /**
     * @param \Packetery\Checkout\Model\ResourceModel\Pricingrule\CollectionFactory $pricingRuleCollectionFactory
     * @param \Packetery\Checkout\Model\ResourceModel\Weightrule\CollectionFactory $weightRuleCollectionFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     */
    public function __construct
    (
        \Packetery\Checkout\Model\ResourceModel\Pricingrule\CollectionFactory $pricingRuleCollectionFactory,
        \Packetery\Checkout\Model\ResourceModel\Weightrule\CollectionFactory $weightRuleCollectionFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
    ) {
        $this->pricingRuleCollectionFactory = $pricingRuleCollectionFactory;
        $this->weightRuleCollectionFactory = $weightRuleCollectionFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->rateResultFactory = $rateResultFactory;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     * @param string $carrierCode
     * @param \Packetery\Checkout\Model\Carrier\Config\AbstractConfig $carrierConfig
     * @param array $methods
     * @param int|null $dynamicCarrierId
     * @return \Magento\Shipping\Model\Rate\Result|null
     */
    public function collectRates(RateRequest $request, string $carrierCode, AbstractConfig $carrierConfig, array $methods, ?int $dynamicCarrierId = null): ?Result
    {
        $result = $this->rateResultFactory->create();

        if ($request->getPackageWeight() > $carrierConfig->getMaxWeight()) {
            return null;
        }

        foreach ($methods as $allowedMethod => $methodLabel) {
            $pricingRule = $this->resolvePricingRule($allowedMethod, $request->getDestCountryId(), $carrierCode, $dynamicCarrierId);
            if ($pricingRule === null || $pricingRule->getEnabled() === false) {
                continue;
            }

            $price = $this->resolvePrice($request, $carrierConfig, $pricingRule);
            if ($price === null) {
                continue; // if cart weight did not match any rule
            }

            $methodCode = new MethodCode($allowedMethod, $dynamicCarrierId);
            $method = $this->createRateMethod(
                $methodCode->toString(),
                $carrierCode,
                $carrierConfig->getTitle(),
                $methodLabel,
                $price
            );

            $result->append($method);
        }

        return $result;
    }

    /**
     * @param string $method
     * @param string $destCountryId
     * @param string $carrierCode
     * @param int|null $carrierId
     * @return \Packetery\Checkout\Model\Pricingrule|null
     */
    public function resolvePricingRule(string $method, string $destCountryId, string $carrierCode, ?int $carrierId = null): ?Pricingrule
    {
        $pricingRuleCollection = $this->pricingRuleCollectionFactory->create();
        $pricingRuleCollection->addFilter('method', $method);
        $pricingRuleCollection->addFilter('country_id', $destCountryId); // iso 2
        $pricingRuleCollection->addFilter('carrier_code', $carrierCode);

        if ($carrierId !== null) {
            $pricingRuleCollection->addFilter('carrier_id', $carrierId);
        }

        return ($pricingRuleCollection->getFirstRecord() ?: null);
    }

    /**
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     * @param \Packetery\Checkout\Model\Carrier\Config\AbstractConfig $config
     * @param \Packetery\Checkout\Model\Pricingrule $pricingRule
     * @return float|null
     */
    protected function resolvePrice(RateRequest $request, AbstractConfig $config, Pricingrule $pricingRule): ?float
    {
        $weightTotal = (float)$request->getPackageWeight();
        $priceTotal = (float)$request->getPackageValue();

        $freeShipping = $this->getFreeShippingThreshold($pricingRule, $config->getFreeShippingThreshold());

        if ($freeShipping !== null && $freeShipping <= $priceTotal) {
            return 0;
        }

        $weightRules = $this->getWeightRulesByPricingRule($pricingRule);
        return $this->resolveWeightedPrice($weightRules, $weightTotal, $config->getMaxWeight());
    }

    /**
     * @param array $weightRules
     * @param float $weightTotal
     * @param float $fallbackWeight
     * @return float|null
     */
    protected function resolveWeightedPrice(array $weightRules, float $weightTotal, float $fallbackWeight): ?float
    {
        usort(
            $weightRules,
            function (Weightrule $a, Weightrule $b) use ($fallbackWeight) {
                $weightA = ($a->getMaxWeight() ?? $fallbackWeight);
                $weightB = ($b->getMaxWeight() ?? $fallbackWeight);
                return $weightA <=> $weightB; // has to be sorted in ASC order
            }
        );

        foreach ($weightRules as $rule) {
            $ruleMaxWeight = $rule->getMaxWeight();
            $rulePrice = $rule->getPrice();

            if ($ruleMaxWeight === null) {
                $ruleMaxWeight = $fallbackWeight;
            }

            if ($weightTotal <= $ruleMaxWeight) {
                return $rulePrice;
            }
        }

        return null;
    }

    /**
     * @param string $packeteryMethod
     * @param string $carrierCode
     * @param string|\Magento\Framework\Phrase $carrierTitle
     * @param string|\Magento\Framework\Phrase $methodTitle
     * @param float $price
     * @return \Magento\Quote\Model\Quote\Address\RateResult\Method
     */
    protected function createRateMethod(string $packeteryMethod, string $carrierCode, $carrierTitle, $methodTitle, float $price): \Magento\Quote\Model\Quote\Address\RateResult\Method
    {
        $method = $this->rateMethodFactory->create();
        $method->setCarrier($carrierCode);

        if (empty($method->getCarrierTitle())) {
            $method->setCarrierTitle($carrierTitle);
        }

        $method->setMethod($packeteryMethod);

        if (empty($method->getMethodTitle())) {
            $method->setMethodTitle($methodTitle);
        }

        $method->setCost($price);
        $method->setPrice($price);

        return $method;
    }

    /**
     * @param \Packetery\Checkout\Model\Pricingrule $pricingRule
     * @return array
     */
    public function getWeightRulesByPricingRule(Pricingrule $pricingRule): array
    {
        $collection = $this->weightRuleCollectionFactory->create();
        $collection->addFilter('packetery_pricing_rule_id', $pricingRule->getId());
        $collection->setOrder('max_weight', 'ASC');
        return $collection->getItems();
    }

    /**
     * @param \Packetery\Checkout\Model\Pricingrule $pricingrule
     * @param float|null $globalFreeShipping
     * @return float|null
     */
    protected function getFreeShippingThreshold(Pricingrule $pricingrule, ?float $globalFreeShipping): ?float
    {
        $countryFreeShipping = $pricingrule->getFreeShipment();

        if (is_numeric($countryFreeShipping)) {
            return $countryFreeShipping;
        }

        if (is_numeric($globalFreeShipping)) {
            return $globalFreeShipping;
        }

        return null;
    }
}
