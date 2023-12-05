<?php

namespace Packetery\spec\Phpro\SoapClient\CodeGenerator\Model;

use Packetery\Phpro\SoapClient\CodeGenerator\Model\Client;
use Packetery\Phpro\SoapClient\CodeGenerator\Model\ClientMethodMap;
use Packetery\Phpro\SoapClient\CodeGenerator\Model\Property;
use Packetery\PhpSpec\ObjectBehavior;
/**
 * Class ClientSpec
 *
 * @package spec\Phpro\SoapClient\CodeGenerator\Model
 * @mixin Property
 * @internal
 */
class ClientSpec extends ObjectBehavior
{
    function let(ClientMethodMap $methods)
    {
        $this->beConstructedWith('MyClient', 'MyNamespace', $methods);
    }
    function it_is_initializable()
    {
        $this->shouldHaveType(Client::class);
    }
    function it_has_a_name()
    {
        $this->getName()->shouldReturn('MyClient');
    }
    function is_has_a_namespace()
    {
        $this->getNamespace()->shouldBe('MyNamespace');
    }
}
