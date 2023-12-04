<?php

namespace Packetery\spec\Phpro\SoapClient\CodeGenerator\Rules;

use Packetery\Phpro\SoapClient\CodeGenerator\Assembler\AssemblerInterface;
use Packetery\Phpro\SoapClient\CodeGenerator\Context\ContextInterface;
use Packetery\Phpro\SoapClient\CodeGenerator\Rules\AssembleRule;
use Packetery\Phpro\SoapClient\CodeGenerator\Rules\RuleInterface;
use Packetery\PhpSpec\ObjectBehavior;
use Packetery\Prophecy\Argument;
/**
 * Class AssembleRuleSpec
 *
 * @package spec\Phpro\SoapClient\CodeGenerator\Rules
 * @mixin AssembleRule
 * @internal
 */
class AssembleRuleSpec extends ObjectBehavior
{
    function let(AssemblerInterface $assembler)
    {
        $this->beConstructedWith($assembler);
    }
    function it_is_initializable()
    {
        $this->shouldHaveType(AssembleRule::class);
    }
    function it_is_a_rule()
    {
        $this->shouldImplement(RuleInterface::class);
    }
    function it_can_apply_if_it_can_assemble(AssemblerInterface $assembler, ContextInterface $context)
    {
        $assembler->canAssemble($context)->willReturn(\true);
        $this->appliesToContext($context)->shouldBe(\true);
    }
    function it_can_not_apply_if_it_can_not_assemble(AssemblerInterface $assembler, ContextInterface $context)
    {
        $assembler->canAssemble($context)->willReturn(\false);
        $this->appliesToContext($context)->shouldBe(\false);
    }
    function it_assembles_the_context_when_applied(AssemblerInterface $assembler, ContextInterface $context)
    {
        $assembler->assemble($context)->shouldBeCalled();
        $this->apply($context);
    }
}
