<?php

namespace Packetery\spec\Phpro\SoapClient\Wsdl\Provider;

use Packetery\Phpro\SoapClient\Wsdl\Provider\MixedWsdlProvider;
use Packetery\Phpro\SoapClient\Wsdl\Provider\WsdlProviderInterface;
use Packetery\PhpSpec\ObjectBehavior;
/** @internal */
class MixedWsdlProviderSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(MixedWsdlProvider::class);
    }
    function it_is_a_wsdl_provider()
    {
        $this->shouldImplement(WsdlProviderInterface::class);
    }
    function it_provides_the_source_as_destination()
    {
        $this->provide('source')->shouldBe('source');
    }
}
