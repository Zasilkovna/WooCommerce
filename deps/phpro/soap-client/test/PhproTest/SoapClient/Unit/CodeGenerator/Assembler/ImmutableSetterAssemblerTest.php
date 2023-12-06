<?php

namespace Packetery\PhproTest\SoapClient\Unit\CodeGenerator\Assembler;

use Packetery\Phpro\SoapClient\CodeGenerator\Assembler\AssemblerInterface;
use Packetery\Phpro\SoapClient\CodeGenerator\Assembler\ImmutableSetterAssembler;
use Packetery\Phpro\SoapClient\CodeGenerator\Assembler\ImmutableSetterAssemblerOptions;
use Packetery\Phpro\SoapClient\CodeGenerator\Context\PropertyContext;
use Packetery\Phpro\SoapClient\CodeGenerator\Model\Property;
use Packetery\Phpro\SoapClient\CodeGenerator\Model\Type;
use Packetery\PHPUnit\Framework\TestCase;
use Packetery\Laminas\Code\Generator\ClassGenerator;
/**
 * Class ImmutableSetterAssemblerTest
 *
 * @package PhproTest\SoapClient\Unit\CodeGenerator\Assembler
 * @internal
 */
class ImmutableSetterAssemblerTest extends TestCase
{
    /**
     * @test
     */
    function it_is_an_assembler()
    {
        $assembler = new ImmutableSetterAssembler();
        $this->assertInstanceOf(AssemblerInterface::class, $assembler);
    }
    /**
     * @test
     */
    function it_can_assemble_property_context()
    {
        $assembler = new ImmutableSetterAssembler();
        $context = $this->createContext();
        $this->assertTrue($assembler->canAssemble($context));
    }
    /**
     * @test
     */
    function it_assembles_a_property()
    {
        $assembler = new ImmutableSetterAssembler();
        $context = $this->createContext();
        $assembler->assemble($context);
        $code = $context->getClass()->generate();
        $expected = <<<CODE
namespace MyNamespace;

class MyType
{

    /**
     * @param string \$prop1
     * @return MyType
     */
    public function withProp1(\$prop1)
    {
        \$new = clone \$this;
        \$new->prop1 = \$prop1;

        return \$new;
    }


}

CODE;
        $this->assertEquals($expected, $code);
    }
    /**
     * @test
     */
    function it_assembles_a_doc_block_that_does_not_wrap()
    {
        $assembler = new ImmutableSetterAssembler();
        $context = $this->createContextWithLongType();
        $assembler->assemble($context);
        $generated = $context->getClass()->generate();
        $expected = <<<CODE
namespace MyNamespace;

class MyType
{

    /**
     * @param \\This\\Is\\My\\Very\\Very\\Long\\Namespace\\And\\Class\\Name\\That\\Should\\Not\\Never\\Ever\\Wrap \$prop1
     * @return MyType
     */
    public function withProp1(\$prop1)
    {
        \$new = clone \$this;
        \$new->prop1 = \$prop1;

        return \$new;
    }


}

CODE;
        $this->assertEquals($expected, $generated);
    }
    /**
     * @test
     */
    function it_assembles_with_no_doc_blocks()
    {
        $assembler = new ImmutableSetterAssembler((new ImmutableSetterAssemblerOptions())->withDocBlocks(\false));
        $context = $this->createContextWithLongType();
        $assembler->assemble($context);
        $generated = $context->getClass()->generate();
        $expected = <<<CODE
namespace MyNamespace;

class MyType
{

    public function withProp1(\$prop1)
    {
        \$new = clone \$this;
        \$new->prop1 = \$prop1;

        return \$new;
    }


}

CODE;
        $this->assertEquals($expected, $generated);
    }
    /**
     * @return PropertyContext
     */
    private function createContext()
    {
        $class = new ClassGenerator('MyType', 'MyNamespace');
        $type = new Type('MyNamespace', 'MyType', [$property = new Property('prop1', 'string', 'ns1')]);
        return new PropertyContext($class, $type, $property);
    }
    /**
     * @test
     */
    function it_assembles_with_type_hints()
    {
        $assembler = new ImmutableSetterAssembler((new ImmutableSetterAssemblerOptions())->withTypeHints());
        $context = $this->createContext();
        $assembler->assemble($context);
        $code = $context->getClass()->generate();
        $expected = <<<CODE
namespace MyNamespace;

class MyType
{

    /**
     * @param string \$prop1
     * @return MyType
     */
    public function withProp1(string \$prop1)
    {
        \$new = clone \$this;
        \$new->prop1 = \$prop1;

        return \$new;
    }


}

CODE;
        $this->assertEquals($expected, $code);
    }
    /**
     * @test
     */
    public function it_assembles_with_return_type() : void
    {
        $assembler = new ImmutableSetterAssembler((new ImmutableSetterAssemblerOptions())->withReturnTypes());
        $context = $this->createContext();
        $assembler->assemble($context);
        $code = $context->getClass()->generate();
        $expected = <<<CODE
namespace MyNamespace;

class MyType
{

    /**
     * @param string \$prop1
     * @return MyType
     */
    public function withProp1(\$prop1) : \\MyNamespace\\MyType
    {
        \$new = clone \$this;
        \$new->prop1 = \$prop1;

        return \$new;
    }


}

CODE;
        $this->assertEquals($expected, $code);
    }
    /**
     * @return PropertyContext
     */
    private function createContextWithLongType()
    {
        $properties = ['prop1' => new Property('prop1', 'Wrap', 'Packetery\\This\\Is\\My\\Very\\Very\\Long\\Namespace\\And\\Class\\Name\\That\\Should\\Not\\Never\\Ever')];
        $class = new ClassGenerator('MyType', 'MyNamespace');
        $type = new Type('MyNamespace', 'MyType', \array_values($properties));
        $property = $properties['prop1'];
        return new PropertyContext($class, $type, $property);
    }
}
