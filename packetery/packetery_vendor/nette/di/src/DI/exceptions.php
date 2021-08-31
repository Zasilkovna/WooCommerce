<?php

/**
 * This file is part of the PacketeryNette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace PacketeryNette\DI;

use PacketeryNette;


/**
 * Service not found exception.
 */
class MissingServiceException extends PacketeryNette\InvalidStateException
{
}


/**
 * Service creation exception.
 */
class ServiceCreationException extends PacketeryNette\InvalidStateException
{
	public function setMessage(string $message): self
	{
		$this->message = $message;
		return $this;
	}
}


/**
 * Not allowed when container is resolving.
 */
class NotAllowedDuringResolvingException extends PacketeryNette\InvalidStateException
{
}


/**
 * Error in configuration.
 */
class InvalidConfigurationException extends PacketeryNette\InvalidStateException
{
}
