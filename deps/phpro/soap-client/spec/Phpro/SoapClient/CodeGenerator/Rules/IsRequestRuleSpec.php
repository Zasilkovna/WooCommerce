<?php

namespace Packetery\spec\Phpro\SoapClient\CodeGenerator\Rules;

use Packetery\Phpro\SoapClient\CodeGenerator\Context\ContextInterface;
use Packetery\Phpro\SoapClient\CodeGenerator\Context\TypeContext;
use Packetery\Phpro\SoapClient\CodeGenerator\Model\Type;
use Packetery\Phpro\SoapClient\CodeGenerator\Rules\RuleInterface;
use Packetery\Phpro\SoapClient\CodeGenerator\Rules\IsRequestRule;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Collection\MethodCollection;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\MetadataInterface;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Model\Method;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Model\Parameter;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Model\XsdType;
use Packetery\PhpSpec\ObjectBehavior;
/**
 * Class IsRequestRuleSpec
 *
 * @package spec\Phpro\SoapClient\CodeGenerator\Rules
 * @mixin IsRequestRule
 * @internal
 */
class IsRequestRuleSpec extends ObjectBehavior
{
    function let(MetadataInterface $metadata, RuleInterface $subRule)
    {
        $metadata->getMethods()->willReturn(new MethodCollection(new Method('method1', [new Parameter('prop1', XsdType::create('Request-Type'))], XsdType::create('string'))));
        $this->beConstructedWith($metadata, $subRule);
    }
    function it_is_initializable()
    {
        $this->shouldHaveType(IsRequestRule::class);
    }
    function it_is_a_rule()
    {
        $this->shouldImplement(RuleInterface::class);
    }
    function it_can_not_apply_to_regular_context(ContextInterface $context)
    {
        $this->appliesToContext($context)->shouldReturn(\false);
    }
    function it_can_apply_to_type_context(RuleInterface $subRule, TypeContext $context)
    {
        $context->getType()->willReturn(new Type('MyNamespace', 'RequestType', []));
        $subRule->appliesToContext($context)->willReturn(\true);
        $this->appliesToContext($context)->shouldReturn(\true);
    }
    function it_can_not_apply_on_invalid_type(RuleInterface $subRule, TypeContext $context)
    {
        $context->getType()->willReturn(new Type('MyNamespace', 'InvalidTypeName', []));
        $subRule->appliesToContext($context)->willReturn(\true);
        $this->appliesToContext($context)->shouldReturn(\false);
    }
    function it_can_apply_if_subrule_does_not_apply(RuleInterface $subRule, TypeContext $context)
    {
        $context->getType()->willReturn(new Type('MyNamespace', 'RequestType', []));
        $subRule->appliesToContext($context)->willReturn(\false);
        $this->appliesToContext($context)->shouldReturn(\false);
    }
    function it_appies_subrule_when_applied(RuleInterface $subRule, ContextInterface $context)
    {
        $subRule->apply($context)->shouldBeCalled();
        $this->apply($context);
    }
}
