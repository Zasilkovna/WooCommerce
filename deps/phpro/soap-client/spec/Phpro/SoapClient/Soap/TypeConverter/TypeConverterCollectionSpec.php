<?php

namespace Packetery\spec\Phpro\SoapClient\Soap\TypeConverter;

use Packetery\Phpro\SoapClient\Exception\InvalidArgumentException;
use Packetery\Phpro\SoapClient\Soap\TypeConverter\DateTypeConverter;
use Packetery\PhpSpec\ObjectBehavior;
use Packetery\Prophecy\Argument;
/** @internal */
class TypeConverterCollectionSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith([new DateTypeConverter()]);
    }
    function it_is_initializable()
    {
        $this->shouldHaveType('Packetery\\Phpro\\SoapClient\\Soap\\TypeConverter\\TypeConverterCollection');
    }
    function it_should_not_be_able_to_add_the_same_converter_twice()
    {
        $this->shouldThrow(InvalidArgumentException::class)->duringAdd(new DateTypeConverter());
    }
    function it_should_know_its_registered_converters()
    {
        $this->has(new DateTypeConverter())->shouldBe(\true);
    }
}
