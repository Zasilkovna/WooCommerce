<?php

namespace Packetery\spec\Phpro\SoapClient\Event\Subscriber;

use Packetery\Phpro\SoapClient\Event\Subscriber\LogSubscriber;
use Packetery\PhpSpec\ObjectBehavior;
use Packetery\Prophecy\Argument;
use Packetery\Psr\Log\LoggerInterface;
use Packetery\Symfony\Component\EventDispatcher\EventSubscriberInterface;
/** @internal */
class LogSubscriberSpec extends ObjectBehavior
{
    function let(LoggerInterface $logger)
    {
        $this->beConstructedWith($logger);
    }
    function it_is_initializable()
    {
        $this->shouldHaveType(LogSubscriber::class);
    }
    function it_should_be_an_event_subscriber()
    {
        $this->shouldImplement(EventSubscriberInterface::class);
    }
}
