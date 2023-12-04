<?php

namespace Packetery\spec\Phpro\SoapClient\CodeGenerator\Model;

use Packetery\Phpro\SoapClient\CodeGenerator\Model\Property;
use Packetery\PhpSpec\ObjectBehavior;
use Packetery\Prophecy\Argument;
/**
 * Class PropertySpec
 *
 * @package spec\Phpro\SoapClient\CodeGenerator\Model
 * @mixin Property
 * @internal
 */
class PropertySpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith('name', 'Type', 'Packetery\\My\\Namespace');
    }
    function it_is_initializable()
    {
        $this->shouldHaveType(Property::class);
    }
    function it_has_a_name()
    {
        $this->getName()->shouldReturn('name');
    }
    function it_has_a_type()
    {
        $this->getType()->shouldReturn('Packetery\\My\\Namespace\\Type');
    }
    function it_has_a_getter_name()
    {
        $this->getterName()->shouldReturn('getName');
    }
    function it_has_a_setter_name()
    {
        $this->setterName()->shouldReturn('setName');
    }
}
