<?php

/**
 * This file is part of the PacketeryNette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace PacketeryNette\DI\Extensions;

use PacketeryNette;


/**
 * Enables registration of other extensions in $config file
 */
final class ExtensionsExtension extends PacketeryNette\DI\CompilerExtension
{
	public function getConfigSchema(): PacketeryNette\Schema\Schema
	{
		return PacketeryNette\Schema\Expect::arrayOf('string|PacketeryNette\DI\Definitions\Statement');
	}


	public function loadConfiguration()
	{
		foreach ($this->getConfig() as $name => $class) {
			if (is_int($name)) {
				$name = null;
			}
			$args = [];
			if ($class instanceof PacketeryNette\DI\Definitions\Statement) {
				[$class, $args] = [$class->getEntity(), $class->arguments];
			}
			if (!is_a($class, PacketeryNette\DI\CompilerExtension::class, true)) {
				throw new PacketeryNette\DI\InvalidConfigurationException("Extension '$class' not found or is not PacketeryNette\\DI\\CompilerExtension descendant.");
			}
			$this->compiler->addExtension($name, (new \ReflectionClass($class))->newInstanceArgs($args));
		}
	}
}
