<?php

namespace Packetery\PhproTest\SoapClient\Unit\CodeGenerator\Assembler;

use Packetery\Phpro\SoapClient\CodeGenerator\Assembler\AssemblerInterface;
use Packetery\Phpro\SoapClient\CodeGenerator\Assembler\ExtendAssembler;
use Packetery\Phpro\SoapClient\CodeGenerator\Context\TypeContext;
use Packetery\Phpro\SoapClient\CodeGenerator\Model\Property;
use Packetery\Phpro\SoapClient\CodeGenerator\Model\Type;
use Packetery\PHPUnit\Framework\TestCase;
use Packetery\Laminas\Code\Generator\ClassGenerator;
/**
 * Class ExtendAssemblerTest
 *
 * @package PhproTest\SoapClient\Unit\CodeGenerator\Assembler
 * @internal
 */
class ExtendAssemblerTest extends TestCase
{
    /**
     * @test
     */
    function it_is_an_assembler()
    {
        $assembler = new ExtendAssembler(\ArrayIterator::class);
        $this->assertInstanceOf(AssemblerInterface::class, $assembler);
    }
    /**
     * @test
     */
    function it_can_assemble_type_context()
    {
        $assembler = new ExtendAssembler(\ArrayIterator::class);
        $context = $this->createContext();
        $this->assertTrue($assembler->canAssemble($context));
    }
    /**
     * @test
     */
    function it_assembles_a_type()
    {
        $assembler = new ExtendAssembler(\ArrayIterator::class);
        $context = $this->createContext();
        $assembler->assemble($context);
        $code = $context->getClass()->generate();
        $expected = <<<CODE
namespace MyNamespace;

use ArrayIterator;

class MyType extends ArrayIterator
{


}

CODE;
        $this->assertEquals($expected, $code);
    }
    /**
     * @test
     */
    function it_assembles_a_type_with_extended_class_name_equal_to_generated_class_name()
    {
        $assembler = new ExtendAssembler('Packetery\\MyNamespace\\MyType');
        $context = $this->createContext();
        $assembler->assemble($context);
        $code = $context->getClass()->generate();
        $expected = <<<CODE
namespace MyNamespace;

class MyType
{


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
        $type = new Type($namespace = 'MyNamespace', 'MyType', [new Property('prop1', 'string', $namespace), new Property('prop2', 'int', $namespace)]);
        return new TypeContext($class, $type);
    }
}
