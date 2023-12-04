<?php

declare (strict_types=1);
namespace Packetery\PhproTest\SoapClient\Unit\Soap\Driver\ExtSoap\Metadata\Manipulators\DuplicateTypes;

use Packetery\Phpro\SoapClient\Soap\Driver\ExtSoap\Metadata\Manipulators\DuplicateTypes\IntersectDuplicateTypesStrategy;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Collection\TypeCollection;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Manipulators\TypesManipulatorInterface;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Model\Type;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Model\XsdType;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Model\Property;
use Packetery\PHPUnit\Framework\TestCase;
/** @internal */
class IntersectDuplicateTypesStrategyTest extends TestCase
{
    public function it_is_a_types_manipulator() : void
    {
        $strategy = new IntersectDuplicateTypesStrategy();
        self::assertInstanceOf(TypesManipulatorInterface::class, $strategy);
    }
    /** @test */
    public function it_can_intersect_duplicate_types() : void
    {
        $strategy = new IntersectDuplicateTypesStrategy();
        $types = new TypeCollection(new Type(XsdType::create('file'), [new Property('prop1', XsdType::create('string')), new Property('prop3', XsdType::create('string'))]), new Type(XsdType::create('file'), [new Property('prop1', XsdType::create('string')), new Property('prop2', XsdType::create('string'))]), new Type(XsdType::create('uppercased'), []), new Type(XsdType::create('Uppercased'), []), new Type(XsdType::create('with-specialchar'), []), new Type(XsdType::create('with*specialchar'), []), new Type(XsdType::create('not-duplicate'), []), new Type(XsdType::create('CASEISDIFFERENT'), []), new Type(XsdType::create('Case-is-different'), []));
        $manipulated = $strategy($types);
        self::assertInstanceOf(TypeCollection::class, $manipulated);
        self::assertEquals([new Type(XsdType::create('file'), [new Property('prop1', XsdType::create('string')), new Property('prop3', XsdType::create('string')), new Property('prop2', XsdType::create('string'))]), new Type(XsdType::create('uppercased'), []), new Type(XsdType::create('with-specialchar'), []), new Type(XsdType::create('not-duplicate'), []), new Type(XsdType::create('CASEISDIFFERENT'), []), new Type(XsdType::create('Case-is-different'), [])], \iterator_to_array($manipulated));
    }
}
