<?php

namespace Packetery\spec\Phpro\SoapClient\CodeGenerator\Context;

use Packetery\Phpro\SoapClient\CodeGenerator\Context\ClassMapContext;
use Packetery\Phpro\SoapClient\CodeGenerator\Context\ContextInterface;
use Packetery\Phpro\SoapClient\CodeGenerator\Model\TypeMap;
use Packetery\PhpSpec\ObjectBehavior;
use Packetery\Prophecy\Argument;
use Packetery\Laminas\Code\Generator\FileGenerator;
/**
 * Class ClassMapContextSpec
 *
 * @package spec\Phpro\SoapClient\CodeGenerator\Context
 * @mixin ClassMapContext
 * @internal
 */
class ClassMapContextSpec extends ObjectBehavior
{
    function let(FileGenerator $fileGenerator, TypeMap $typeMap)
    {
        $this->beConstructedWith($fileGenerator, $typeMap, 'ClassMap', 'Packetery\\App\\Mynamespace');
    }
    function it_is_initializable()
    {
        $this->shouldHaveType(ClassMapContext::class);
    }
    function it_is_a_context()
    {
        $this->shouldImplement(ContextInterface::class);
    }
    function it_has_a_file_generator(FileGenerator $fileGenerator)
    {
        $this->getFile()->shouldReturn($fileGenerator);
    }
    function it_has_a_typemap(TypeMap $typeMap)
    {
        $this->getTypeMap()->shouldReturn($typeMap);
    }
}
