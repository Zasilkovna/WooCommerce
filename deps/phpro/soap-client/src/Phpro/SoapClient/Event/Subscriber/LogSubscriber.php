<?php

namespace Packetery\Phpro\SoapClient\Event\Subscriber;

use Packetery\Phpro\SoapClient\Event\RequestEvent;
use Packetery\Phpro\SoapClient\Event\ResponseEvent;
use Packetery\Phpro\SoapClient\Event\FaultEvent;
use Packetery\Phpro\SoapClient\Events;
use Packetery\Psr\Log\LoggerInterface;
use Packetery\Symfony\Component\EventDispatcher\EventSubscriberInterface;
/** @internal */
class LogSubscriber implements EventSubscriberInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    /**
     * @param RequestEvent $event
     */
    public function onClientRequest(RequestEvent $event)
    {
        $this->logger->info(\sprintf('[phpro/soap-client] request: call "%s" with params %s', $event->getMethod(), \print_r($event->getRequest(), \true)));
    }
    /**
     * @param ResponseEvent $event
     */
    public function onClientResponse(ResponseEvent $event)
    {
        $this->logger->info(\sprintf('[phpro/soap-client] response: %s', \print_r($event->getResponse(), \true)));
    }
    /**
     * @param FaultEvent $event
     */
    public function onClientFault(FaultEvent $event)
    {
        $this->logger->error(\sprintf('[phpro/soap-client] fault "%s" for request "%s" with params %s', $event->getSoapException()->getMessage(), $event->getRequestEvent()->getMethod(), \print_r($event->getRequestEvent()->getRequest(), \true)));
    }
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() : array
    {
        return array(Events::REQUEST => 'onClientRequest', Events::RESPONSE => 'onClientResponse', Events::FAULT => 'onClientFault');
    }
}
