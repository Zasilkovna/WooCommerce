<?php

declare (strict_types=1);
namespace Packetery\PhproTest\SoapClient\Unit\Soap\Engine\Metadata\Manipulators;

use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Collection\MethodCollection;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Manipulators\MethodsManipulatorChain;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Manipulators\MethodsManipulatorInterface;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Model\Method;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Model\XsdType;
use Packetery\PHPUnit\Framework\TestCase;
/** @internal */
class MethodsManipulatorChainTest extends TestCase
{
    /** @test */
    public function it_is_a_method_manipulator() : void
    {
        self::assertInstanceOf(MethodsManipulatorInterface::class, new MethodsManipulatorChain());
    }
    /** @test */
    public function it_does_not_touch_methods_with_no_manipulator() : void
    {
        $methods = new MethodCollection();
        $chain = new MethodsManipulatorChain();
        $result = $chain($methods);
        self::assertSame($methods, $result);
    }
    /** @test */
    public function it_manipulates_methods_collection() : void
    {
        $methods = new MethodCollection();
        $chain = new MethodsManipulatorChain(new class implements MethodsManipulatorInterface
        {
            public function __invoke(MethodCollection $allMethods) : MethodCollection
            {
                return new MethodCollection(...\array_merge(\iterator_to_array($allMethods), [new Method('method', [], XsdType::create('Response'))]));
            }
        }, new class implements MethodsManipulatorInterface
        {
            public function __invoke(MethodCollection $allMethods) : MethodCollection
            {
                return new MethodCollection(...\array_merge(\iterator_to_array($allMethods), [new Method('method2', [], XsdType::create('Response'))]));
            }
        });
        $result = $chain($methods);
        self::assertNotSame($methods, $result);
        self::assertInstanceOf(MethodCollection::class, $result);
        self::assertCount(2, $result);
        self::assertEquals([new Method('method', [], XsdType::create('Response')), new Method('method2', [], XsdType::create('Response'))], \iterator_to_array($result));
    }
}
