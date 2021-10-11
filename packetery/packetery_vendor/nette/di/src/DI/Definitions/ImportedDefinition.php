<?php

/**
 * This file is part of the PacketeryNette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace PacketeryNette\DI\Definitions;

use PacketeryNette;
use PacketeryNette\DI\PhpGenerator;


/**
 * Imported service injected to the container.
 */
final class ImportedDefinition extends Definition
{
	/** @return static */
	public function setType(?string $type)
	{
		return parent::setType($type);
	}


	public function resolveType(PacketeryNette\DI\Resolver $resolver): void
	{
	}


	public function complete(PacketeryNette\DI\Resolver $resolver): void
	{
	}


	public function generateMethod(PacketeryNette\PhpGenerator\Method $method, PhpGenerator $generator): void
	{
		$method->setReturnType('void')
			->setBody(
				'throw new PacketeryNette\\DI\\ServiceCreationException(?);',
				["Unable to create imported service '{$this->getName()}', it must be added using addService()"]
			);
	}


	/** @deprecated use '$def instanceof ImportedDefinition' */
	public function isDynamic(): bool
	{
		return true;
	}
}
