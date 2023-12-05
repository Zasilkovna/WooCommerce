<?php

namespace Packetery\spec\Phpro\SoapClient\CodeGenerator;

use Packetery\Phpro\SoapClient\CodeGenerator\ClassMapGenerator;
use Packetery\Phpro\SoapClient\CodeGenerator\Context\ClassMapContext;
use Packetery\Phpro\SoapClient\CodeGenerator\GeneratorInterface;
use Packetery\Phpro\SoapClient\CodeGenerator\Model\TypeMap;
use Packetery\Phpro\SoapClient\CodeGenerator\Rules\RuleSetInterface;
use Packetery\PhpSpec\ObjectBehavior;
use Packetery\Prophecy\Argument;
use Packetery\Laminas\Code\Generator\FileGenerator;
/**
 * Class ClassMapGeneratorSpec
 *
 * @package spec\Phpro\SoapClient\CodeGenerator
 * @mixin ClassMapGenerator
 * @internal
 */
class ClassMapGeneratorSpec extends ObjectBehavior
{
    function let(RuleSetInterface $ruleSet)
    {
        $this->beConstructedWith($ruleSet, 'ClassMap', 'Packetery\\App\\Mynamespace');
    }
    function it_is_initializable()
    {
        $this->shouldHaveType(ClassMapGenerator::class);
    }
    function it_is_a_generator()
    {
        $this->shouldImplement(GeneratorInterface::class);
    }
    function it_generates_classmaps(RuleSetInterface $ruleSet, FileGenerator $file, TypeMap $typeMap)
    {
        $ruleSet->applyRules(Argument::type(ClassMapContext::class))->shouldBeCalled();
        $file->generate()->willReturn('code');
        $this->generate($file, $typeMap)->shouldReturn('code');
    }
}
