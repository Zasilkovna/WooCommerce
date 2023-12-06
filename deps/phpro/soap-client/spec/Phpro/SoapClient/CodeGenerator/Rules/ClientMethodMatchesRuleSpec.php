<?php

namespace Packetery\spec\Phpro\SoapClient\CodeGenerator\Rules;

use Packetery\Phpro\SoapClient\CodeGenerator\Context\ClientMethodContext;
use Packetery\Phpro\SoapClient\CodeGenerator\Context\ContextInterface;
use Packetery\Phpro\SoapClient\CodeGenerator\Context\PropertyContext;
use Packetery\Phpro\SoapClient\CodeGenerator\Model\ClientMethod;
use Packetery\Phpro\SoapClient\CodeGenerator\Model\Type;
use Packetery\Phpro\SoapClient\CodeGenerator\Rules\RuleInterface;
use Packetery\Phpro\SoapClient\CodeGenerator\Rules\ClientMethodMatchesRule;
use Packetery\PhpSpec\ObjectBehavior;
/**
 * Class ClientMethodMatchesRuleSpec
 *
 * @package spec\Phpro\SoapClient\CodeGenerator\Rules
 * @mixin ClientMethodMatchesRule
 * @internal
 */
class ClientMethodMatchesRuleSpec extends ObjectBehavior
{
    function let(RuleInterface $subRule)
    {
        $this->beConstructedWith($subRule, '/^myClientMethod$/');
    }
    function it_is_initializable()
    {
        $this->shouldHaveType(ClientMethodMatchesRule::class);
    }
    function it_is_a_rule()
    {
        $this->shouldImplement(RuleInterface::class);
    }
    function it_can_not_apply_to_regular_context(ContextInterface $context)
    {
        $this->appliesToContext($context)->shouldReturn(\false);
    }
    function it_can_apply_to_client_method_context(RuleInterface $subRule, ClientMethodContext $context)
    {
        $context->getMethod()->willReturn(new ClientMethod('myClientMethod', [], 'string'));
        $subRule->appliesToContext($context)->willReturn(\true);
        $this->appliesToContext($context)->shouldReturn(\true);
    }
    function it_can_not_apply_on_unmatched_regex(RuleInterface $subRule, ClientMethodContext $context)
    {
        $context->getMethod()->willReturn(new ClientMethod('myInvalidClientMethod', [], 'string'));
        $subRule->appliesToContext($context)->willReturn(\true);
        $this->appliesToContext($context)->shouldReturn(\false);
    }
    function it_can_not_apply_if_subrule_does_not_apply(RuleInterface $subRule, ClientMethodContext $context)
    {
        $context->getMethod()->willReturn(new ClientMethod('myClientMethod', [], 'string'));
        $subRule->appliesToContext($context)->willReturn(\false);
        $this->appliesToContext($context)->shouldReturn(\false);
    }
    function it_applies_subrule_when_applied(RuleInterface $subRule, ContextInterface $context)
    {
        $subRule->apply($context)->shouldBeCalled();
        $this->apply($context);
    }
}
