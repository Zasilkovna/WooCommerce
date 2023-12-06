<?php

namespace Packetery\spec\Phpro\SoapClient\CodeGenerator\Model;

use Packetery\Phpro\SoapClient\CodeGenerator\Model\ClientMethodMap;
use Packetery\PhpSpec\ObjectBehavior;
/**
 * Class MethodMapSpec
 *
 * @package spec\Phpro\SoapClient\CodeGenerator\Model
 * @internal
 */
class ClientMethodMapSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith([], 'MyNamespace');
    }
    function it_is_initializable()
    {
        $this->shouldHaveType(ClientMethodMap::class);
    }
    function it_has_methods()
    {
        $this->getMethods()->shouldReturn([]);
    }
}
