<?php

namespace Packetery\Phpro\SoapClient\Console;

use Packetery\Phpro\SoapClient\Console\Command;
use Packetery\Phpro\SoapClient\Console\Event\Subscriber\LaminasCodeValidationSubscriber;
use Packetery\Phpro\SoapClient\Console\Helper\ConfigHelper;
use Packetery\Phpro\SoapClient\Util\Filesystem;
use Packetery\Symfony\Component\Console\Application as SymfonyApplication;
use Packetery\Symfony\Component\Console\Helper\HelperSet;
use Packetery\Symfony\Component\EventDispatcher\EventDispatcher;
use Packetery\Symfony\Component\EventDispatcher\EventDispatcherInterface;
/**
 * Class Application
 *
 * @package Phpro\SoapClient\Console
 * @internal
 */
class Application extends SymfonyApplication
{
    const APP_NAME = 'SoapClient';
    const APP_VERSION = '0.1.0';
    /**
     * Set up application:
     */
    public function __construct()
    {
        $this->setDispatcher($this->createEventDispatcher());
        parent::__construct(self::APP_NAME, self::APP_VERSION);
    }
    /**
     * {@inheritdoc}
     */
    protected function getDefaultCommands() : array
    {
        $filesystem = new Filesystem();
        $commands = parent::getDefaultCommands();
        $commands[] = new Command\GenerateTypesCommand($filesystem);
        $commands[] = new Command\GenerateClassmapCommand($filesystem);
        $commands[] = new Command\GenerateClientCommand($filesystem);
        $commands[] = new Command\GenerateConfigCommand($filesystem);
        $commands[] = new Command\GenerateClientFactoryCommand($filesystem);
        $commands[] = new Command\WizardCommand();
        return $commands;
    }
    protected function getDefaultHelperSet() : HelperSet
    {
        $set = parent::getDefaultHelperSet();
        $set->set(new ConfigHelper(new Filesystem()));
        return $set;
    }
    private function createEventDispatcher() : EventDispatcherInterface
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new LaminasCodeValidationSubscriber());
        return $dispatcher;
    }
}
