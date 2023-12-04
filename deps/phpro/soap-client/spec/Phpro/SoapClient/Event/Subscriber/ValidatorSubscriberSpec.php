<?php

namespace Packetery\spec\Phpro\SoapClient\Event\Subscriber;

use Packetery\Phpro\SoapClient\Client;
use Packetery\Phpro\SoapClient\Event\RequestEvent;
use Packetery\Phpro\SoapClient\Event\Subscriber\ValidatorSubscriber;
use Packetery\Phpro\SoapClient\Exception\RequestException;
use Packetery\Phpro\SoapClient\Type\RequestInterface;
use Packetery\PhpSpec\ObjectBehavior;
use Packetery\Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Packetery\Symfony\Component\Validator\ConstraintViolation;
use Packetery\Symfony\Component\Validator\ConstraintViolationList;
use Packetery\Symfony\Component\Validator\Validator\ValidatorInterface;
/** @internal */
class ValidatorSubscriberSpec extends ObjectBehavior
{
    function let(ValidatorInterface $validator)
    {
        $this->beConstructedWith($validator);
    }
    function it_is_initializable()
    {
        $this->shouldHaveType(ValidatorSubscriber::class);
    }
    function it_should_be_an_event_subscriber()
    {
        $this->shouldImplement(EventSubscriberInterface::class);
    }
    function it_throws_exception_on_invalid_requests(ValidatorInterface $validator, Client $client, RequestInterface $request, ConstraintViolation $violation1, ConstraintViolation $violation2)
    {
        $event = new RequestEvent($client->getWrappedObject(), 'method', $request->getWrappedObject());
        $violation1->getMessage()->willReturn('error 1');
        $violation2->getMessage()->willReturn('error 2');
        $validator->validate($request)->willReturn(new ConstraintViolationList([$violation1->getWrappedObject(), $violation2->getWrappedObject()]));
        $this->shouldThrow(RequestException::class)->duringOnClientRequest($event);
    }
    function it_does_not_throw_exception_onnvalid_requests(ValidatorInterface $validator, Client $client, RequestInterface $request)
    {
        $event = new RequestEvent($client->getWrappedObject(), 'method', $request->getWrappedObject());
        $validator->validate($request)->willReturn(new ConstraintViolationList([]));
        $this->shouldNotThrow(RequestException::class)->duringOnClientRequest($event);
    }
}
