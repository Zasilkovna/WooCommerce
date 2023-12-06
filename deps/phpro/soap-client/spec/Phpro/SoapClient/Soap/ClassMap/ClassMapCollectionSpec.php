<?php

namespace Packetery\spec\Phpro\SoapClient\Soap\ClassMap;

use Packetery\Phpro\SoapClient\Exception\InvalidArgumentException;
use Packetery\Phpro\SoapClient\Soap\ClassMap\ClassMap;
use Packetery\Phpro\SoapClient\Soap\ClassMap\ClassMapCollection;
use Packetery\PhpSpec\ObjectBehavior;
use Packetery\Prophecy\Argument;
/** @internal */
class ClassMapCollectionSpec extends ObjectBehavior
{
    function let(ClassMap $classMap)
    {
        $classMap->getWsdlType()->willReturn('WsdlType');
        $classMap->getPhpClassName()->willReturn('PhpClass');
        $this->beConstructedWith([$classMap]);
    }
    function it_is_initializable()
    {
        $this->shouldHaveType(ClassMapCollection::class);
    }
    function it_knows_the_attached_classmaps(ClassMap $classMap)
    {
        $this->has($classMap)->shouldBe(\true);
    }
    function it_should_not_be_able_to_add_the_same_classmap_twice(ClassMap $classMap)
    {
        $this->shouldThrow(InvalidArgumentException::class)->duringAdd($classMap);
    }
    function it_should_add_a_classmap(ClassMap $classMap2)
    {
        $this->has($classMap2)->shouldBe(\false);
        $this->add($classMap2);
        $this->has($classMap2)->shouldBe(\true);
    }
}
