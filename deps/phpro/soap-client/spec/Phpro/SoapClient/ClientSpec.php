<?php

namespace Packetery\spec\Phpro\SoapClient;

use Packetery\Phpro\SoapClient\Client;
use Packetery\Phpro\SoapClient\ClientInterface;
use Packetery\Phpro\SoapClient\Soap\Engine\EngineInterface;
use Packetery\Phpro\SoapClient\Soap\SoapClient;
use Packetery\PhpSpec\ObjectBehavior;
use Packetery\Prophecy\Argument;
use Packetery\Symfony\Component\EventDispatcher\EventDispatcherInterface;
/** @internal */
class ClientSpec extends ObjectBehavior
{
    function let(EngineInterface $engine, EventDispatcherInterface $dispatcher)
    {
        $this->beConstructedWith($engine, $dispatcher);
    }
    function it_is_initializable()
    {
        $this->shouldHaveType(Client::class);
    }
    function it_should_be_a_client()
    {
        $this->shouldImplement(ClientInterface::class);
    }
}
