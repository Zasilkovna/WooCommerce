<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit;

use Packetery\Checkout\Model\Carrier\Methods;

class AllowedMethodsTest extends \Packetery\Checkout\Test\BaseTest
{
    public function testGetFinalAllowedMethods() {
        $this->assertFinals(
            [Methods::ADDRESS_DELIVERY],
            [Methods::ADDRESS_DELIVERY],
            [Methods::ADDRESS_DELIVERY]
        );

        $this->assertFinals(
            [Methods::PICKUP_POINT_DELIVERY],
            [Methods::ADDRESS_DELIVERY],
            []
        );

        $this->assertFinals(
            [Methods::PICKUP_POINT_DELIVERY, 'adasdadadadasdad'],
            [Methods::ADDRESS_DELIVERY],
            []
        );

        $this->assertFinals(
            [Methods::PICKUP_POINT_DELIVERY, Methods::ADDRESS_DELIVERY, 'adasdadadadasdad'],
            [Methods::PICKUP_POINT_DELIVERY],
            [Methods::PICKUP_POINT_DELIVERY]
        );

        $this->assertFinals(
            [Methods::ADDRESS_DELIVERY],
            [Methods::PICKUP_POINT_DELIVERY, Methods::ADDRESS_DELIVERY, 'adasdadadadasdad'],
            [Methods::ADDRESS_DELIVERY]
        );

        $this->assertFinals(
            [Methods::ADDRESS_DELIVERY],
            ['adasdadadadasdad'],
            []
        );

        $this->assertFinals(
            [],
            [Methods::DIRECT_ADDRESS_DELIVERY],
            [Methods::DIRECT_ADDRESS_DELIVERY] // because of allowed methods
        );
    }

    /**
     * @param array $userMethods
     * @param array $dynamicMethods
     * @param array $resultMethods
     */
    private function assertFinals(array $userMethods, array $dynamicMethods, array $resultMethods): void {
        /** @var \Packetery\Checkout\Model\Carrier\AbstractBrain $proxy */
        $proxy = $this->createProxy(\Packetery\Checkout\Model\Carrier\Imp\PacketeryPacketaDynamic\Brain::class, [], []);

        $methodSelect = $this->createProxy(\Packetery\Checkout\Model\Carrier\Imp\PacketeryPacketaDynamic\MethodSelect::class, [], []);

        $config = $this->createProxy(
            \Packetery\Checkout\Model\Carrier\Imp\PacketeryPacketaDynamic\Config::class,
            ['data' => []],
            [
                'getAllowedMethods' => $userMethods,
            ]
        );

        $dynamicConfig = $this->createProxy(
            \Packetery\Checkout\Model\Carrier\Imp\PacketeryPacketaDynamic\DynamicConfig::class,
            ['data' => []],
            [
                'getConfig' => $config,
                'getAllowedMethods' => $dynamicMethods,
            ]
        );

        $result = $proxy->getFinalAllowedMethods($dynamicConfig, $methodSelect);
        $this->assertCount(count($resultMethods), $result);
        $this->assertEquals(array_values($resultMethods), array_values($result));
    }
}
