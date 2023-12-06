<?php

namespace Packetery\spec\Phpro\SoapClient\Wsdl\Provider;

use Packetery\Phpro\SoapClient\Wsdl\Provider\InMemoryWsdlProvider;
use Packetery\Phpro\SoapClient\Wsdl\Provider\WsdlProviderInterface;
use Packetery\PhpSpec\ObjectBehavior;
/** @internal */
class InMemoryWsdlProviderSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(InMemoryWsdlProvider::class);
    }
    function it_is_a_wsdl_provider()
    {
        $this->shouldImplement(WsdlProviderInterface::class);
    }
    function it_provides_an_in_memory_data_source()
    {
        $this->provide('source')->shouldBe('data://text/plain;base64,' . \base64_encode('source'));
    }
}
