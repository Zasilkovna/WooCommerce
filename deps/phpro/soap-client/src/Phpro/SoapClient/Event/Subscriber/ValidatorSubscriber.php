<?php

namespace Packetery\Phpro\SoapClient\Event\Subscriber;

use Packetery\Phpro\SoapClient\CodeGenerator\GeneratorInterface;
use Packetery\Phpro\SoapClient\Event\RequestEvent;
use Packetery\Phpro\SoapClient\Events;
use Packetery\Phpro\SoapClient\Exception\RequestException;
use Packetery\Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Packetery\Symfony\Component\Validator\ConstraintViolationInterface;
use Packetery\Symfony\Component\Validator\ConstraintViolationListInterface;
use Packetery\Symfony\Component\Validator\Validator\ValidatorInterface;
/** @internal */
class ValidatorSubscriber implements EventSubscriberInterface
{
    /**
     * @var ValidatorInterface
     */
    private $validator;
    /**
     * Constructor
     *
     * @param ValidatorInterface $validator
     */
    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() : array
    {
        return [Events::REQUEST => 'onClientRequest'];
    }
    /**
     * @param RequestEvent $event
     *
     * @throws \Phpro\SoapClient\Exception\RequestException
     */
    public function onClientRequest(RequestEvent $event)
    {
        $errors = $this->validator->validate($event->getRequest());
        if (\count($errors)) {
            throw new RequestException(self::toString($errors));
        }
    }
    private static function toString(ConstraintViolationListInterface $errors) : string
    {
        $strErrors = [];
        /** @var ConstraintViolationInterface $error */
        foreach ($errors as $error) {
            $strErrors[] = $error->getMessage();
        }
        return \implode(GeneratorInterface::EOL, $strErrors);
    }
}
