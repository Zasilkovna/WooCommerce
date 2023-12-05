<?php

namespace Packetery\Phpro\SoapClient\Console\Event\Subscriber;

use Packetery\Phpro\SoapClient\CodeGenerator\Util\Validator;
use Packetery\Symfony\Component\Console\ConsoleEvents;
use Packetery\Symfony\Component\Console\Event\ConsoleCommandEvent;
use Packetery\Symfony\Component\Console\Style\SymfonyStyle;
use Packetery\Symfony\Component\EventDispatcher\EventSubscriberInterface;
/**
 * Check if laminas code is installed when generating code
 * Show a helpful error message for when it is not
 *
 * Class LaminasCodeValidationListener
 * @package Phpro\SoapClient\Event\Subscriber
 * @internal
 */
class LaminasCodeValidationSubscriber implements EventSubscriberInterface
{
    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents() : array
    {
        return [ConsoleEvents::COMMAND => 'onCommand'];
    }
    public function onCommand(ConsoleCommandEvent $event)
    {
        if (!Validator::commandRequiresLaminasCode($event->getCommand()->getName())) {
            return;
        }
        if (Validator::laminasCodeIsInstalled()) {
            return;
        }
        $io = new SymfonyStyle($event->getInput(), $event->getOutput());
        $io->error(['laminas-code not installed, require it with this command:', 'composer require --dev laminas/laminas-code:^3.1.0']);
        $event->disableCommand();
    }
}
