<?php

namespace Packetery\spec\Phpro\SoapClient\Soap\Driver\ExtSoap\Metadata\Visitor;

use Packetery\Phpro\SoapClient\Soap\Driver\ExtSoap\Metadata\Visitor\XsdTypeVisitorInterface;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Model\XsdType;
use Packetery\PhpSpec\ObjectBehavior;
use Packetery\Prophecy\Argument;
use Packetery\Phpro\SoapClient\Soap\Driver\ExtSoap\Metadata\Visitor\SimpleTypeVisitor;
/**
 * Class SimpleTypeVisitorSpec
 * @internal
 */
class SimpleTypeVisitorSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(SimpleTypeVisitor::class);
    }
    function it_is_an_xsd_type_visitor()
    {
        $this->shouldHaveType(XsdTypeVisitorInterface::class);
    }
    function it_returns_null_on_invalid_entry()
    {
        $this('list listType {,member1,member2}')->shouldBe(null);
        $this('list listType')->shouldBe(null);
        $this('union unionType {,member1,member2}')->shouldBe(null);
        $this('union unionType')->shouldBe(null);
        $this('struct x {}')->shouldBe(null);
    }
    function it_returns_type_on_valid_entry()
    {
        $this('string simpleType')->shouldBeLike(XsdType::create('simpleType')->withBaseType('string'));
    }
}
