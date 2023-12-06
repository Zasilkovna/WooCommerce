<?php

namespace Packetery\spec\Phpro\SoapClient\Soap\Driver\ExtSoap\Generator;

use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Collection\MethodCollection;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\MetadataInterface;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Model\Method;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Model\Parameter;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Model\XsdType;
use Packetery\PhpSpec\ObjectBehavior;
use Packetery\Prophecy\Argument;
use Packetery\Phpro\SoapClient\Soap\Driver\ExtSoap\Generator\DummyMethodArgumentsGenerator;
/**
 * Class DummyMethodArgumentsGeneratorSpec
 * @internal
 */
class DummyMethodArgumentsGeneratorSpec extends ObjectBehavior
{
    function let(MetadataInterface $metadata)
    {
        $this->beConstructedWith($metadata);
    }
    function it_is_initializable()
    {
        $this->shouldHaveType(DummyMethodArgumentsGenerator::class);
    }
    function it_can_parse_dummy_arguments(MetadataInterface $metadata)
    {
        $metadata->getMethods()->willReturn(new MethodCollection(new Method('method', [new Parameter('param1', XsdType::create('string')), new Parameter('param1', XsdType::create('integer'))], XsdType::create('string'))));
        $this->generateForSoapCall('method')->shouldBe([null, null]);
    }
}
