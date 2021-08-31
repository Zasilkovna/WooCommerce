<?php

/**
 * This file is part of the PacketeryNette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace PacketeryNette;

if (false) {
	/** alias for PacketeryNette\Bootstrap\Configurator */
	class Configurator extends Bootstrap\Configurator
	{
	}
} elseif (!class_exists(Configurator::class)) {
	class_alias(Bootstrap\Configurator::class, Configurator::class);
}
