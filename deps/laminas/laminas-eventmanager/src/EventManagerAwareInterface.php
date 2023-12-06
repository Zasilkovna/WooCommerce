<?php

namespace Packetery\Laminas\EventManager;

/**
 * Interface to automate setter injection for an EventManager instance
 * @internal
 */
interface EventManagerAwareInterface extends EventsCapableInterface
{
    /**
     * Inject an EventManager instance
     *
     * @return void
     */
    public function setEventManager(EventManagerInterface $eventManager);
}
