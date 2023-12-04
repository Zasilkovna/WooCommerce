<?php

namespace Packetery\spec\Phpro\SoapClient\Event;

use Packetery\Phpro\SoapClient\Client;
use Packetery\Phpro\SoapClient\Event\SoapEvent;
use Packetery\Phpro\SoapClient\Event\RequestEvent;
use Packetery\Phpro\SoapClient\Event\ResponseEvent;
use Packetery\Phpro\SoapClient\Type\ResultInterface;
use Packetery\PhpSpec\ObjectBehavior;
/** @internal */
class ResponseEventSpec extends ObjectBehavior
{
    function let(Client $client, RequestEvent $requestEvent, ResultInterface $response)
    {
        $this->beConstructedWith($client, $requestEvent, $response);
    }
    function it_is_initializable()
    {
        $this->shouldHaveType(ResponseEvent::class);
    }
    function it_is_an_event()
    {
        $this->shouldHaveType(SoapEvent::class);
    }
    function it_should_know_the_request_event(RequestEvent $requestEvent)
    {
        $this->getRequestEvent()->shouldReturn($requestEvent);
    }
    function it_should_know_the_result(ResultInterface $response)
    {
        $this->getResponse()->shouldReturn($response);
    }
}
