<?php

namespace Packetery\Laminas\EventManager;

/**
 * Interface indicating that an object composes an EventManagerInterface instance.
 * @internal
 */
interface EventsCapableInterface
{
    /**
     * Retrieve the event manager
     *
     * Lazy-loads an EventManager instance if none registered.
     *
     * @return EventManagerInterface
     */
    public function getEventManager();
}
