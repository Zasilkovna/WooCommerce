<?php

namespace Packetery\spec\Phpro\SoapClient\CodeGenerator\Context;

use Packetery\Phpro\SoapClient\CodeGenerator\Context\ClientContext;
use Packetery\Phpro\SoapClient\CodeGenerator\Context\TypeContext;
use Packetery\PhpSpec\ObjectBehavior;
/**
 * Class TypeContextSpec
 *
 * @package spec\Phpro\SoapClient\CodeGenerator\Context
 * @mixin TypeContext
 * @internal
 */
class ClientContextSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith('MyClient', 'Packetery\\App\\Client');
    }
    function it_is_initializable()
    {
        $this->shouldHaveType(ClientContext::class);
    }
    function is_has_a_name()
    {
        $this->getName()->shouldBe('MyClient');
    }
    function it_has_a_namespace()
    {
        $this->getNamespace()->shouldBe('Packetery\\App\\Client');
    }
}
