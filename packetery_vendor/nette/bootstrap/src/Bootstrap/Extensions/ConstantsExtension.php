<?php

/**
 * This file is part of the PacketeryNette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace PacketeryNette\Bootstrap\Extensions;

use PacketeryNette;


/**
 * Constant definitions.
 */
final class ConstantsExtension extends PacketeryNette\DI\CompilerExtension
{
	public function loadConfiguration()
	{
		foreach ($this->getConfig() as $name => $value) {
			$this->initialization->addBody('define(?, ?);', [$name, $value]);
		}
	}
}
