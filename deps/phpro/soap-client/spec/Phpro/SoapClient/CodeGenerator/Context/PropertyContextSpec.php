<?php

namespace Packetery\spec\Phpro\SoapClient\CodeGenerator\Context;

use Packetery\Phpro\SoapClient\CodeGenerator\Context\ContextInterface;
use Packetery\Phpro\SoapClient\CodeGenerator\Context\PropertyContext;
use Packetery\Phpro\SoapClient\CodeGenerator\Model\Property;
use Packetery\Phpro\SoapClient\CodeGenerator\Model\Type;
use Packetery\PhpSpec\ObjectBehavior;
use Packetery\Prophecy\Argument;
use Packetery\Laminas\Code\Generator\ClassGenerator;
/**
 * Class PropertyContextSpec
 *
 * @package spec\Phpro\SoapClient\CodeGenerator\Context
 * @mixin PropertyContext
 * @internal
 */
class PropertyContextSpec extends ObjectBehavior
{
    function let(ClassGenerator $class, Type $type, Property $property)
    {
        $this->beConstructedWith($class, $type, $property);
    }
    function it_is_initializable()
    {
        $this->shouldHaveType(PropertyContext::class);
    }
    function it_is_a_context()
    {
        $this->shouldImplement(ContextInterface::class);
    }
    function it_has_a_class_generator(ClassGenerator $class)
    {
        $this->getClass()->shouldReturn($class);
    }
    function it_has_a_type(Type $type)
    {
        $this->getType()->shouldReturn($type);
    }
    function it_has_a_property(Property $property)
    {
        $this->getProperty()->shouldReturn($property);
    }
}
