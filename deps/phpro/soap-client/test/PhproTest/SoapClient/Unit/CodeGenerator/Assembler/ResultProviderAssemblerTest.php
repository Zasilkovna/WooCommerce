<?php

namespace Packetery\PhproTest\SoapClient\Unit\CodeGenerator\Assembler;

use Packetery\Phpro\SoapClient\CodeGenerator\Assembler\AssemblerInterface;
use Packetery\Phpro\SoapClient\CodeGenerator\Assembler\ResultProviderAssembler;
use Packetery\Phpro\SoapClient\CodeGenerator\Context\TypeContext;
use Packetery\Phpro\SoapClient\CodeGenerator\Model\Property;
use Packetery\Phpro\SoapClient\CodeGenerator\Model\Type;
use Packetery\Phpro\SoapClient\Type\MixedResult;
use Packetery\PHPUnit\Framework\TestCase;
use Packetery\Laminas\Code\Generator\ClassGenerator;
/**
 * Class ResultProviderAssemblerTest
 *
 * @package PhproTest\SoapClient\Unit\CodeGenerator\Assembler
 * @internal
 */
class ResultProviderAssemblerTest extends TestCase
{
    /**
     * @test
     */
    function it_is_an_assembler()
    {
        $assembler = new ResultProviderAssembler();
        $this->assertInstanceOf(AssemblerInterface::class, $assembler);
    }
    /**
     * @test
     */
    function it_can_assemble_type_context()
    {
        $assembler = new ResultProviderAssembler();
        $context = $this->createContext();
        $this->assertTrue($assembler->canAssemble($context));
    }
    /**
     * @test
     */
    function it_assembles_a_type()
    {
        $assembler = new ResultProviderAssembler();
        $context = $this->createContext();
        $assembler->assemble($context);
        $code = $context->getClass()->generate();
        $expected = <<<CODE
namespace MyNamespace;

use Phpro\\SoapClient\\Type\\ResultProviderInterface;
use Phpro\\SoapClient\\Type\\ResultInterface;

class MyType implements ResultProviderInterface
{

    /**
     * @return \\MyNamespace\\SomeClass|ResultInterface
     */
    public function getResult() : \\Phpro\\SoapClient\\Type\\ResultInterface
    {
        return \$this->prop1;
    }


}

CODE;
        $this->assertEquals($expected, $code);
    }
    /**
     * @test
     */
    function it_assembles_a_type_with_wrapper_class()
    {
        $assembler = new ResultProviderAssembler(MixedResult::class);
        $context = $this->createContext();
        $assembler->assemble($context);
        $code = $context->getClass()->generate();
        $expected = <<<CODE
namespace MyNamespace;

use Phpro\\SoapClient\\Type\\ResultProviderInterface;
use Phpro\\SoapClient\\Type\\MixedResult;

class MyType implements ResultProviderInterface
{

    /**
     * @return MixedResult
     */
    public function getResult() : \\Phpro\\SoapClient\\Type\\ResultInterface
    {
        return new MixedResult(\$this->prop1);
    }


}

CODE;
        $this->assertEquals($expected, $code);
    }
    /**
     * @test
     */
    function it_assembles_a_type_with_wrapper_class_with_prefixed_slash()
    {
        $assembler = new ResultProviderAssembler('\\' . MixedResult::class);
        $context = $this->createContext();
        $assembler->assemble($context);
        $code = $context->getClass()->generate();
        $expected = <<<CODE
namespace MyNamespace;

use Phpro\\SoapClient\\Type\\ResultProviderInterface;
use Phpro\\SoapClient\\Type\\MixedResult;

class MyType implements ResultProviderInterface
{

    /**
     * @return MixedResult
     */
    public function getResult() : \\Phpro\\SoapClient\\Type\\ResultInterface
    {
        return new MixedResult(\$this->prop1);
    }


}

CODE;
        $this->assertEquals($expected, $code);
    }
    /**
     * @return TypeContext
     */
    private function createContext()
    {
        $class = new ClassGenerator('MyType', 'MyNamespace');
        $type = new Type($namespace = 'MyNamespace', 'MyType', [new Property('prop1', 'SomeClass', $namespace)]);
        return new TypeContext($class, $type);
    }
}
