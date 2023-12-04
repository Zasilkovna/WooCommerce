<?php

namespace Packetery\spec\Phpro\SoapClient\CodeGenerator\Context;

use Packetery\Phpro\SoapClient\CodeGenerator\Config\Config;
use Packetery\Phpro\SoapClient\CodeGenerator\Context\ClassMapContext;
use Packetery\Phpro\SoapClient\CodeGenerator\Context\ClientContext;
use Packetery\Phpro\SoapClient\CodeGenerator\Context\ContextInterface;
use Packetery\Phpro\SoapClient\CodeGenerator\Model\TypeMap;
use Packetery\PhpSpec\ObjectBehavior;
use Packetery\Prophecy\Argument;
use Packetery\Phpro\SoapClient\CodeGenerator\Context\ClientFactoryContext;
use Packetery\Laminas\Code\Generator\FileGenerator;
/**
 * Class ClientFactoryContextSpec
 * @internal
 */
class ClientFactoryContextSpec extends ObjectBehavior
{
    function let()
    {
        $clientContext = new ClientContext('Myclient', 'Packetery\\App\\Client');
        $classMapContext = new ClassMapContext(new FileGenerator(), new TypeMap('ns', []), 'Myclassmap', 'Packetery\\App\\Classmap');
        $this->beConstructedWith($clientContext, $classMapContext);
    }
    function it_is_initializable()
    {
        $this->shouldHaveType(ClientFactoryContext::class);
    }
    function it_is_a_context()
    {
        $this->shouldImplement(ContextInterface::class);
    }
    function it_returns_client_fqcn()
    {
        $this->getClientFqcn()->shouldBe('Packetery\\App\\Client\\Myclient');
    }
    function it_returns_classmap_fqcn()
    {
        $this->getClassmapFqcn()->shouldBe('Packetery\\App\\Classmap\\Myclassmap');
    }
    function it_returns_a_client_name()
    {
        $this->getClientName()->shouldBe('Myclient');
    }
    function it_returns_the_client_namespace()
    {
        $this->getClientNamespace()->shouldBe('Packetery\\App\\Client');
    }
    function it_returns_the_classmap_name()
    {
        $this->getClassmapName()->shouldBe('Myclassmap');
    }
    function it_returns_the_classmap_namespace()
    {
        $this->getClassmapNamespace()->shouldBe('Packetery\\App\\Classmap');
    }
}
