<?php

namespace Packetery\Laminas\EventManager;

/**
 * Interface indicating that an object composes or can compose a
 * SharedEventManagerInterface instance.
 * @internal
 */
interface SharedEventsCapableInterface
{
    /**
     * Retrieve the shared event manager, if composed.
     *
     * @return null|SharedEventManagerInterface
     */
    public function getSharedManager();
}
