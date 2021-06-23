<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Rate\Result;
use Packetery\Checkout\Model\Carrier\AbstractBrain;
use Packetery\Checkout\Model\Carrier\Methods;
use Packetery\Checkout\Model\Pricing;
use Packetery\Checkout\Model\Pricingrule;
use Packetery\Checkout\Model\Weightrule;
use Packetery\Checkout\Test\BaseTest;
use PHPUnit\Framework\MockObject\MockObject;

class PricingServiceTest extends BaseTest
{
    /**
     * @throws \ReflectionException
     */
    public function testService()
    {
        $service = $this->createService();

        $weightRules = [
            $this->createWeightRule(49, 5),
            $this->createWeightRule(79, 10),
        ];

        $this->assertEquals(null, $this->invokeMethod($service, 'resolveWeightedPrice', [$weightRules, 11, 999]));
        $this->assertEquals(79, $this->invokeMethod($service, 'resolveWeightedPrice', [$weightRules, 10, 999]));
        $this->assertEquals(79, $this->invokeMethod($service, 'resolveWeightedPrice', [$weightRules, 8, 999]));
        $this->assertEquals(49, $this->invokeMethod($service, 'resolveWeightedPrice', [$weightRules, 5, 999]));
        $this->assertEquals(49, $this->invokeMethod($service, 'resolveWeightedPrice', [$weightRules, 1, 999]));
        $this->assertEquals(49, $this->invokeMethod($service, 'resolveWeightedPrice', [$weightRules, 0, 999]));
        $this->assertEquals(49, $this->invokeMethod($service, 'resolveWeightedPrice', [$weightRules, -2, 999]));

        $weightRules = [
            $this->createWeightRule(49, 5),
            $this->createWeightRule(79, 10),
            $this->createWeightRule(89, 15),
        ];

        $this->assertEquals(null, $this->invokeMethod($service, 'resolveWeightedPrice', [[], 20, 999]));
        $this->assertEquals(null, $this->invokeMethod($service, 'resolveWeightedPrice', [$weightRules, 20, 999]));
        $this->assertEquals(89, $this->invokeMethod($service, 'resolveWeightedPrice', [$weightRules, 11, 999]));
        $this->assertEquals(79, $this->invokeMethod($service, 'resolveWeightedPrice', [$weightRules, 10, 999]));
        $this->assertEquals(79, $this->invokeMethod($service, 'resolveWeightedPrice', [$weightRules, 8, 999]));
        $this->assertEquals(49, $this->invokeMethod($service, 'resolveWeightedPrice', [$weightRules, 5, 999]));
        $this->assertEquals(49, $this->invokeMethod($service, 'resolveWeightedPrice', [$weightRules, 1, 999]));
        $this->assertEquals(49, $this->invokeMethod($service, 'resolveWeightedPrice', [$weightRules, 0, 999]));
        $this->assertEquals(49, $this->invokeMethod($service, 'resolveWeightedPrice', [$weightRules, -2, 999]));
    }

    /**
     * @throws \ReflectionException
     */
    public function testRateCollection()
    {
        $pricingRule = $this->createPricingRule(20000, 'CZ');
        $weightRules = [
            $this->createWeightRule(41, null),
            $this->createWeightRule(44, 3),
            $this->createWeightRule(58, 6),
            $this->createWeightRule(76.88, 9),
        ];

        $methods = $this->createMethodsWithLabels([Methods::PICKUP_POINT_DELIVERY]);
        $result = $this->collectRates($pricingRule, $weightRules, 500000, 300, 'CZ', 10, null, $methods);
        $this->assertNull($result); // cart weight exceeds all rules

        $pricingRule = $this->createPricingRule(20000, 'CZ');
        $weightRules = [
            $this->createWeightRule(41, null),
            $this->createWeightRule(44, 3),
            $this->createWeightRule(58, 6),
            $this->createWeightRule(76.88, 9),
        ];

        $result = $this->collectRates($pricingRule, $weightRules, 5, 300, 'CZ', 10, null, $methods);
        $this->assertRateMethod($result, [
            'carrier_title' => 'title',
            'method' => 'pickupPointDelivery',
            'carrier' => 'packetery',
            'cost' => 58,
        ]);

        $result = $this->collectRates($pricingRule, $weightRules, 11, 300, 'CZ', 10, null, $methods);
        $this->assertNull($result);

        $result = $this->collectRates($pricingRule, $weightRules, 10, 50000, 'CZ', 10, null, $methods);
        $this->assertRateMethod($result, [
            'cost' => 0
        ]);

        $result = $this->collectRates($pricingRule, $weightRules, 10, 50000, 'CZ', 1, null, $methods);
        $this->assertNull($result, 'max global weight has priority over free shipment');

        $result = $this->collectRates(null, [], 5, 300, 'DE', 10, 333.58, $methods);
        $this->assertNotNull($result);
        $rates = $result->getAllRates();
        $this->assertEmpty($rates, 'For empty pricing rule there are no rates due to country per method limitation. Default price fallback was removed.');
    }

    /**
     * @throws \ReflectionException
     */
    public function testAddressDeliveryMethodRateCollection()
    {
        $pricingRule = $this->createPricingRule(400, 'CZ');
        $weightRules = [
            $this->createWeightRule(100.01, 10),
            $this->createWeightRule(100.01, 4.9),
        ];

        $methods = $this->createMethodsWithLabels([Methods::ADDRESS_DELIVERY]);
        $result = $this->collectRates($pricingRule, $weightRules, 5, 300, $pricingRule->getCountryId(), 10.0, 333.58, $methods);
        $this->assertRateMethod($result, [
            'cost' => 100.01, // free shipping
            'method' => Methods::ADDRESS_DELIVERY, // for CZ there is address delivery
        ]);

        $result = $this->collectRates($pricingRule, $weightRules, 5, 400, $pricingRule->getCountryId(), 10.0, 333.58, $methods);
        $this->assertRateMethod($result, [
            'cost' => 0, // free shipping
            'method' => Methods::ADDRESS_DELIVERY, // for CZ there is address delivery
        ]);

        $result = $this->collectRates($pricingRule, $weightRules, 1000000, 400, $pricingRule->getCountryId(), 10.0, 333.58, $methods);
        $this->assertNull($result);

        $methods = $this->createMethodsWithLabels([]);
        $result = $this->collectRates($pricingRule, $weightRules, 10, 400, $pricingRule->getCountryId(), 10.0, 333.58, $methods);
        $this->assertEmpty($result->getAllRates(), 'empty methods results in empty result');

        $methods = $this->createMethodsWithLabels([Methods::ADDRESS_DELIVERY, Methods::PICKUP_POINT_DELIVERY]);
        $result = $this->collectRates($pricingRule, $weightRules, 10, 400, $pricingRule->getCountryId(), 10.0, 333.58, $methods);
        $this->assertNotEmpty($result->getAllRates());
    }

    /**
     * @param $defaultPrice
     * @param $maxWeight
     * @param $freeShipment
     * @return \Packetery\Checkout\Model\Pricing\Service|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createService()
    {
        $service = $this->createMock(Pricing\Service::class);
        return $service;
    }

    /**
     * @param array $methods
     * @return \stdClass
     */
    protected function createObject($methods = [])
    {
        $object = new \stdClass();

        foreach ($methods as $method => $callback) {
            $object->$method = $callback;
        }

        return $object;
    }

    /**
     * @param array $methods
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function createFactoryWithObject($methods = [])
    {
        return $this->createFactoryMock($this->createObject($methods));
    }

    /**
     * @param $willCreate
     * @param null $classsName
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function createFactoryMock($willCreate, $classsName = null)
    {
        $factory = $this->getMockBuilder(($classsName ?: \stdClass::class))
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->setMethods(
                [
                    'create'
                ]
            )
            ->getMock();

        $factory->method('create')->willReturn($willCreate);
        return $factory;
    }

    /**
     * @param float|null $freeShipment
     * @param string|null $countryId
     * @return \Packetery\Checkout\Model\Pricingrule|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createPricingRule(?float $freeShipment, ?string $countryId)
    {
        $weightRule = $this->createMock(Pricingrule::class);
        $weightRule->method('getFreeShipment')->willReturn($freeShipment);
        $weightRule->method('getCountryId')->willReturn($countryId);
        $weightRule->method('getEnabled')->willReturn(true);
        return $weightRule;
    }

    /**
     * @param float $price
     * @param float|null $maxWeightKg
     * @return \Packetery\Checkout\Model\Weightrule|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createWeightRule(float $price, ?float $maxWeightKg)
    {
        $weightRule = $this->createMock(Weightrule::class);
        $weightRule->method('getPrice')->willReturn($price);
        $weightRule->method('getMaxWeight')->willReturn($maxWeightKg);
        return $weightRule;
    }

    private function collectRates(?Pricingrule $pricingRule, array $weightRules, int $cartWeight, int $cartValue, string $country, float $maxGlobalWeight, $globalfreeShipment, array $methods): ?\Magento\Shipping\Model\Rate\Result {
        /** @var \Packetery\Checkout\Model\Pricing\Service|MockObject $service */
        $service = $this->createProxy(
            Pricing\Service::class,
            [
                'rateResultFactory' => $this->createFactoryMock($this->createProxy(Result::class), \Magento\Shipping\Model\Rate\ResultFactory::class),
                'rateMethodFactory' => $this->createFactoryMock($this->createProxy(Method::class, ['priceCurrency' => $this->createMock(PriceCurrencyInterface::class)]), MethodFactory::class),
            ],
            ['getWeightRulesByPricingRule' => $weightRules, 'resolvePricingRule' => $pricingRule]
        );

        $request = $this->createProxyWithMethods(
            \Magento\Quote\Model\Quote\Address\RateRequest::class,
            [],
            [],
            ['getPackageWeight' => $cartWeight, 'getPackageValue' => $cartValue, 'getDestCountryId' => $country]
        );

        $config = $this->createMock(\Packetery\Checkout\Model\Carrier\Imp\Packetery\Config::class);
        $config->method('getMaxWeight')->willReturn($maxGlobalWeight);
        $config->method('getFreeShippingThreshold')->willReturn($globalfreeShipment);
        $config->method('getTitle')->willReturn('title');

        $result = $service->collectRates($request, AbstractBrain::PREFIX, $config, $methods);

        return $result;
    }

    private function createMethodsWithLabels(array $methods): array {
        $finalMethods = [];

        $methodSelect = new \Packetery\Checkout\Model\Carrier\Imp\Packetery\MethodSelect();
        foreach ($methods as $method) {
            $finalMethods[$method] = $methodSelect->getLabelByValue($method);
        }

        return $finalMethods;
    }

    private function assertRateMethod($result, array $rateMethod) {
        $this->assertNotNull($result, 'collected result is null or empty');

        $rates = $result->getAllRates();
        $this->assertNotEmpty($rates);

        /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
        $method = array_shift($rates);

        foreach ($rateMethod as $key => $item) {
            $this->assertEquals($rateMethod[$key], $method->getData($key));
        }
    }
}
