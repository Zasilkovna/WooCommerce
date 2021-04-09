<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Rate\ResultFactory;
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
        $service = $this->createService(100, 10, null);

        $weightRules = [
            $this->createWeightRule(49, 5),
            $this->createWeightRule(79, 10),
        ];

        $this->assertEquals(null, $this->invokeMethod($service, 'resolveWeightedPrice', [$weightRules, 11]));
        $this->assertEquals(79, $this->invokeMethod($service, 'resolveWeightedPrice', [$weightRules, 10]));
        $this->assertEquals(79, $this->invokeMethod($service, 'resolveWeightedPrice', [$weightRules, 8]));
        $this->assertEquals(49, $this->invokeMethod($service, 'resolveWeightedPrice', [$weightRules, 5]));
        $this->assertEquals(49, $this->invokeMethod($service, 'resolveWeightedPrice', [$weightRules, 1]));
        $this->assertEquals(49, $this->invokeMethod($service, 'resolveWeightedPrice', [$weightRules, 0]));
        $this->assertEquals(49, $this->invokeMethod($service, 'resolveWeightedPrice', [$weightRules, -2]));

        $weightRules = [
            $this->createWeightRule(89, 15),
            $this->createWeightRule(49, 5),
            $this->createWeightRule(79, 10),
        ];

        $this->assertEquals(null, $this->invokeMethod($service, 'resolveWeightedPrice', [[], 20]));
        $this->assertEquals(null, $this->invokeMethod($service, 'resolveWeightedPrice', [$weightRules, 20]));
        $this->assertEquals(89, $this->invokeMethod($service, 'resolveWeightedPrice', [$weightRules, 11]));
        $this->assertEquals(79, $this->invokeMethod($service, 'resolveWeightedPrice', [$weightRules, 10]));
        $this->assertEquals(79, $this->invokeMethod($service, 'resolveWeightedPrice', [$weightRules, 8]));
        $this->assertEquals(49, $this->invokeMethod($service, 'resolveWeightedPrice', [$weightRules, 5]));
        $this->assertEquals(49, $this->invokeMethod($service, 'resolveWeightedPrice', [$weightRules, 1]));
        $this->assertEquals(49, $this->invokeMethod($service, 'resolveWeightedPrice', [$weightRules, 0]));
        $this->assertEquals(49, $this->invokeMethod($service, 'resolveWeightedPrice', [$weightRules, -2]));
    }

    /**
     * @throws \ReflectionException
     */
    public function testRateCollection()
    {
        $pricingRule = $this->createPricingRule(20000, 'CZ');
        $weightRules = [
            $this->createWeightRule(76.88, 9),
            $this->createWeightRule(58, 6),
            $this->createWeightRule(44, 3),
        ];

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
            ['getPackageWeight' => 5, 'getPackageValue' => 300, 'getDestCountryId' => 'CZ']
        );

        $config = $this->createMock(\Packetery\Checkout\Model\Carrier\PacketeryConfig::class);
        $config->method('getDefaultPrice')->willReturn(100.0);
        $config->method('getMaxWeight')->willReturn(10.0);
        $config->method('getFreeShippingThreshold')->willReturn(null);
        $config->method('getTitle')->willReturn('title');
        $config->method('getName')->willReturn('name');

        $result = $service->collectRates(new Pricing\Request($request, $config, 'packetery'));
        $this->assertNotNull($result);

        $rates = $result->getAllRates();
        $this->assertNotEmpty($rates);

        /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
        $method = array_shift($rates);

        $this->assertEquals('name', $method->getData('method_title'));
        $this->assertEquals('title', $method->getData('carrier_title'));
        $this->assertEquals('packetery', $method->getData('method'));
        $this->assertEquals('packetery', $method->getData('carrier'));
        $this->assertEquals(58, $method->getData('cost'));


        $request = $this->createProxyWithMethods(
            \Magento\Quote\Model\Quote\Address\RateRequest::class,
            [],
            [],
            ['getPackageWeight' => 11, 'getPackageValue' => 300, 'getDestCountryId' => 'CZ']
        );

        $result = $service->collectRates(new Pricing\Request($request, $config, 'packetery'));
        $this->assertNull($result);

        $request = $this->createProxyWithMethods(
            \Magento\Quote\Model\Quote\Address\RateRequest::class,
            [],
            [],
            ['getPackageWeight' => 10, 'getPackageValue' => 50000, 'getDestCountryId' => 'CZ']
        );

        $result = $service->collectRates(new Pricing\Request($request, $config, 'packetery'));
        $this->assertNotNull($result);

        $rates = $result->getAllRates();
        $this->assertNotEmpty($rates);

        /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
        $method = array_shift($rates);
        $this->assertEquals(0, $method->getData('cost'));

        $pricingRule = null;
        $weightRules = [];

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
            ['getPackageWeight' => 5, 'getPackageValue' => 300, 'getDestCountryId' => 'DE']
        );

        $config = $this->createMock(\Packetery\Checkout\Model\Carrier\PacketeryConfig::class);
        $config->method('getDefaultPrice')->willReturn(100.0);
        $config->method('getMaxWeight')->willReturn(10.0);
        $config->method('getFreeShippingThreshold')->willReturn(333.58);
        $config->method('getTitle')->willReturn('title');
        $config->method('getName')->willReturn('name');

        $result = $service->collectRates(new Pricing\Request($request, $config, 'packetery'));
        $this->assertNotNull($result);

        $rates = $result->getAllRates();
        $this->assertNotEmpty($rates);

        /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
        $method = array_shift($rates);
        $this->assertEquals(100, $method->getData('cost'));
    }

    /**
     * @param $defaultPrice
     * @param $maxWeight
     * @param $freeShipment
     * @return \Packetery\Checkout\Model\Pricing\Service|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createService($defaultPrice, $maxWeight, $freeShipment)
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
        $factory = $this->getMockBuilder($classsName ?: \stdClass::class)
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
        return $weightRule;
    }

    /**
     * @param float $price
     * @param float $maxWeightKg
     * @return \Packetery\Checkout\Model\Weightrule|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createWeightRule(float $price, float $maxWeightKg)
    {
        $weightRule = $this->createMock(Weightrule::class);
        $weightRule->method('getPrice')->willReturn($price);
        $weightRule->method('getMaxWeight')->willReturn($maxWeightKg);
        return $weightRule;
    }
}
