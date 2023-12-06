<?php

namespace Packetery\spec\Phpro\SoapClient\Event\Dispatcher;

use Packetery\Phpro\SoapClient\Event\Dispatcher\EventDispatcherInterface;
use Packetery\Phpro\SoapClient\Event\SoapEvent;
use Packetery\PhpSpec\ObjectBehavior;
use Packetery\Phpro\SoapClient\Event\Dispatcher\PsrEventDispatcher;
use Packetery\Psr\EventDispatcher\EventDispatcherInterface as PsrEventDispatcherImplementation;
/** @internal */
class PsrEventDispatcherSpec extends ObjectBehavior
{
    public function let(PsrEventDispatcherImplementation $dispatcher) : void
    {
        $this->beConstructedWith($dispatcher);
    }
    public function it_is_initializable()
    {
        $this->shouldHaveType(PsrEventDispatcher::class);
    }
    public function it_is_a_soap_event_dispatcher() : void
    {
        $this->shouldImplement(EventDispatcherInterface::class);
    }
    public function it_can_dispatch_events(PsrEventDispatcherImplementation $dispatcher, SoapEvent $event) : void
    {
        $dispatcher->dispatch($event)->shouldBeCalled();
        $this->dispatch($event, 'doesntmatter')->shouldBe($event);
    }
}
