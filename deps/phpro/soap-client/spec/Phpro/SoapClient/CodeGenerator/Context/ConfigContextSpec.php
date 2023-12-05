<?php

namespace Packetery\spec\Phpro\SoapClient\CodeGenerator\Context;

use Packetery\Phpro\SoapClient\CodeGenerator\Context\ContextInterface;
use Packetery\PhpSpec\ObjectBehavior;
use Packetery\Prophecy\Argument;
use Packetery\Phpro\SoapClient\CodeGenerator\Context\ConfigContext;
/**
 * Class ConfigContextSpec
 * @internal
 */
class ConfigContextSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(ConfigContext::class);
    }
    function it_is_a_context()
    {
        $this->shouldImplement(ContextInterface::class);
    }
    function it_adds_setters()
    {
        $this->addSetter('setTest', 'test\'run');
        $this->getSetters()->shouldBeArray();
        $this->getSetters()['setTest']->shouldBe('test\'run');
    }
}
