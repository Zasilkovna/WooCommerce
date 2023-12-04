<?php

namespace Packetery\spec\Phpro\SoapClient\CodeGenerator\Rules;

use Packetery\Phpro\SoapClient\CodeGenerator\Context\ContextInterface;
use Packetery\Phpro\SoapClient\CodeGenerator\Rules\RuleInterface;
use Packetery\Phpro\SoapClient\CodeGenerator\Rules\RuleSet;
use Packetery\Phpro\SoapClient\CodeGenerator\Rules\RuleSetInterface;
use Packetery\PhpSpec\ObjectBehavior;
use Packetery\Prophecy\Argument;
/**
 * Class RuleSetSpec
 *
 * @package spec\Phpro\SoapClient\CodeGenerator\Rules
 * @mixin RuleSet
 * @internal
 */
class RuleSetSpec extends ObjectBehavior
{
    function let(RuleInterface $rule)
    {
        $this->beConstructedWith([$rule]);
    }
    function it_is_initializable()
    {
        $this->shouldHaveType(RuleSet::class);
    }
    function it_is_a_rule_set()
    {
        $this->shouldImplement(RuleSetInterface::class);
    }
    function it_can_apply_rules(RuleInterface $rule, ContextInterface $context)
    {
        $rule->appliesToContext($context)->willReturn(\true);
        $rule->apply($context)->shouldBeCalled();
        $this->applyRules($context);
    }
    function it_can_skip_rules(RuleInterface $rule, ContextInterface $context)
    {
        $rule->appliesToContext($context)->willReturn(\false);
        $rule->apply($context)->shouldNotBeCalled();
        $this->applyRules($context);
    }
}
