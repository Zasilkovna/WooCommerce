<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Packetery\Nette\DI;

use Packetery\Nette;
/**
 * Service not found exception.
 * @internal
 */
class MissingServiceException extends \Packetery\Nette\InvalidStateException
{
}
/**
 * Service creation exception.
 * @internal
 */
class ServiceCreationException extends \Packetery\Nette\InvalidStateException
{
    public function setMessage(string $message) : self
    {
        $this->message = $message;
        return $this;
    }
}
/**
 * Not allowed when container is resolving.
 * @internal
 */
class NotAllowedDuringResolvingException extends \Packetery\Nette\InvalidStateException
{
}
/**
 * Error in configuration.
 * @internal
 */
class InvalidConfigurationException extends \Packetery\Nette\InvalidStateException
{
}
