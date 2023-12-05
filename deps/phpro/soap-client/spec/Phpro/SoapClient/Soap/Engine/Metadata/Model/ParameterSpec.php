<?php

namespace Packetery\spec\Phpro\SoapClient\Soap\Engine\Metadata\Model;

use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Model\XsdType;
use Packetery\PhpSpec\ObjectBehavior;
use Packetery\Prophecy\Argument;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Model\Parameter;
/**
 * Class ParameterSpec
 * @internal
 */
class ParameterSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith('name', XsdType::create('type'));
    }
    function it_is_initializable()
    {
        $this->shouldHaveType(Parameter::class);
    }
    function it_contains_a_name()
    {
        $this->getName()->shouldBe('name');
    }
    function it_contains_a_type()
    {
        $this->getType()->shouldBeLike(XsdType::create('type'));
    }
}
