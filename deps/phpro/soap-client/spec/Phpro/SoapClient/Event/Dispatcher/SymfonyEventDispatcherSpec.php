<?php

namespace Packetery\spec\Phpro\SoapClient\Event\Dispatcher;

use Packetery\Phpro\SoapClient\Event\Dispatcher\EventDispatcherInterface;
use Packetery\Phpro\SoapClient\Event\SoapEvent;
use Packetery\PhpSpec\ObjectBehavior;
use Packetery\Phpro\SoapClient\Event\Dispatcher\SymfonyEventDispatcher;
use Packetery\Symfony\Component\EventDispatcher\EventDispatcherInterface as SymfonyEventDispatcherImplementation;
use Packetery\Symfony\Contracts\EventDispatcher\EventDispatcherInterface as SymfonyEventDispatcherContract;
/** @internal */
class SymfonyEventDispatcherSpec extends ObjectBehavior
{
    public function let(SymfonyEventDispatcherImplementation $dispatcher) : void
    {
        $this->beConstructedWith($dispatcher);
    }
    public function it_is_initializable()
    {
        $this->shouldHaveType(SymfonyEventDispatcher::class);
    }
    public function it_is_a_soap_event_dispatcher() : void
    {
        $this->shouldImplement(EventDispatcherInterface::class);
    }
    public function it_can_dispatch_events(SymfonyEventDispatcherImplementation $dispatcher, SoapEvent $event) : void
    {
        $eventName = 'someEvent';
        $arg1 = $dispatcher->getWrappedObject() instanceof SymfonyEventDispatcherContract ? $event : $eventName;
        $arg2 = $dispatcher->getWrappedObject() instanceof SymfonyEventDispatcherContract ? $eventName : $event;
        $dispatcher->dispatch($arg1, $arg2)->shouldBeCalled();
        $this->dispatch($event, $eventName)->shouldBe($event);
    }
}
