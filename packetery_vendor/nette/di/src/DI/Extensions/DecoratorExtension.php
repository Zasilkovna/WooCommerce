<?php

/**
 * This file is part of the PacketeryNette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace PacketeryNette\DI\Extensions;

use PacketeryNette;
use PacketeryNette\DI\Definitions;
use PacketeryNette\Schema\Expect;

/**
 * Decorators for services.
 */
final class DecoratorExtension extends PacketeryNette\DI\CompilerExtension
{
	public function getConfigSchema(): PacketeryNette\Schema\Schema
	{
		return Expect::arrayOf(
			Expect::structure([
				'setup' => Expect::list(),
				'tags' => Expect::array(),
				'inject' => Expect::bool(),
			])
		);
	}


	public function beforeCompile()
	{
		$this->getContainerBuilder()->resolve();
		foreach ($this->config as $type => $info) {
			if (!class_exists($type) && !interface_exists($type)) {
				throw new PacketeryNette\DI\InvalidConfigurationException("Decorated class '$type' not found.");
			}
			if ($info->inject !== null) {
				$info->tags[InjectExtension::TAG_INJECT] = $info->inject;
			}
			$this->addSetups($type, PacketeryNette\DI\Helpers::filterArguments($info->setup));
			$this->addTags($type, PacketeryNette\DI\Helpers::filterArguments($info->tags));
		}
	}


	public function addSetups(string $type, array $setups): void
	{
		foreach ($this->findByType($type) as $def) {
			if ($def instanceof Definitions\FactoryDefinition) {
				$def = $def->getResultDefinition();
			}
			foreach ($setups as $setup) {
				if (is_array($setup)) {
					$setup = new Definitions\Statement(key($setup), array_values($setup));
				}
				$def->addSetup($setup);
			}
		}
	}


	public function addTags(string $type, array $tags): void
	{
		$tags = PacketeryNette\Utils\Arrays::normalize($tags, true);
		foreach ($this->findByType($type) as $def) {
			$def->setTags($def->getTags() + $tags);
		}
	}


	private function findByType(string $type): array
	{
		return array_filter($this->getContainerBuilder()->getDefinitions(), function (Definitions\Definition $def) use ($type): bool {
			return is_a($def->getType(), $type, true)
				|| ($def instanceof Definitions\FactoryDefinition && is_a($def->getResultType(), $type, true));
		});
	}
}
