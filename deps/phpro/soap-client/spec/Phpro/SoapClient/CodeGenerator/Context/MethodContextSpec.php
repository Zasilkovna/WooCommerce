<?php

namespace Packetery\spec\Phpro\SoapClient\CodeGenerator\Context;

use Packetery\Phpro\SoapClient\CodeGenerator\Context\ContextInterface;
use Packetery\Phpro\SoapClient\CodeGenerator\Context\ClientMethodContext;
use Packetery\Phpro\SoapClient\CodeGenerator\Context\TypeContext;
use Packetery\Phpro\SoapClient\CodeGenerator\Model\ClientMethod;
use Packetery\PhpSpec\ObjectBehavior;
use Packetery\Laminas\Code\Generator\ClassGenerator;
/**
 * Class ClientMethodContextSpec
 *
 * @package spec\Phpro\SoapClient\CodeGenerator\Context
 * @mixin TypeContext
 * @internal
 */
class ClientMethodContextSpec extends ObjectBehavior
{
    function let(ClassGenerator $class, ClientMethod $method)
    {
        $this->beConstructedWith($class, $method);
    }
    function it_is_initializable()
    {
        $this->shouldHaveType(ClientMethodContext::class);
    }
    function it_is_a_context()
    {
        $this->shouldImplement(ContextInterface::class);
    }
    function it_has_a_class_generator(ClassGenerator $class)
    {
        $this->getClass()->shouldReturn($class);
    }
    function it_has_a_method(ClientMethod $method)
    {
        $this->getMethod()->shouldReturn($method);
    }
}
