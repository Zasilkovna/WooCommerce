<?php

namespace Packetery\spec\Phpro\SoapClient\CodeGenerator\Model;

use Packetery\Phpro\SoapClient\CodeGenerator\Model\Property;
use Packetery\Phpro\SoapClient\CodeGenerator\Model\Type;
use Packetery\Phpro\SoapClient\CodeGenerator\Model\TypeMap;
use Packetery\PhpSpec\ObjectBehavior;
use Packetery\Prophecy\Argument;
/**
 * Class TypeMapSpec
 *
 * @package spec\Phpro\SoapClient\CodeGenerator\Model
 * @mixin TypeMap
 * @internal
 */
class TypeMapSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith($namespace = 'MyNamespace', [new Type($namespace, 'type1', [new Property('prop1', 'string', $namespace)])]);
    }
    function it_is_initializable()
    {
        $this->shouldHaveType(TypeMap::class);
    }
    function it_has_a_namespace()
    {
        $this->getNamespace()->shouldReturn('MyNamespace');
    }
    function it_has_types()
    {
        $types = $this->getTypes();
        $types[0]->shouldReturnAnInstanceOf(Type::class);
        $types[0]->getXsdName()->shouldBe('type1');
    }
}
