<?php

namespace Packetery\Psr\Log;

/**
 * Describes a logger-aware instance.
 * @internal
 */
interface LoggerAwareInterface
{
    /**
     * Sets a logger instance on the object.
     *
     * @param LoggerInterface $logger
     *
     * @return void
     */
    public function setLogger(LoggerInterface $logger) : void;
}
