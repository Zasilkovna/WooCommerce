<?php

/**
 * This file is part of the PacketeryNette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace PacketeryNette\DI\Definitions;

use PacketeryNette;
use PacketeryNette\Utils\Strings;


/**
 * Assignment or calling statement.
 *
 * @property string|array|Definition|Reference|null $entity
 */
final class Statement implements PacketeryNette\Schema\DynamicParameter
{
	use PacketeryNette\SmartObject;

	/** @var array */
	public $arguments;

	/** @var string|array|Definition|Reference|null */
	private $entity;


	/**
	 * @param  string|array|Definition|Reference|null  $entity
	 */
	public function __construct($entity, array $arguments = [])
	{
		if (
			$entity !== null
			&& !is_string($entity) // Class, @service, not, tags, types, PHP literal, entity::member
			&& !$entity instanceof Definition
			&& !$entity instanceof Reference
			&& !(is_array($entity)
				&& array_keys($entity) === [0, 1]
				&& (is_string($entity[0])
					|| $entity[0] instanceof self
					|| $entity[0] instanceof Reference
					|| $entity[0] instanceof Definition)
		)) {
			throw new PacketeryNette\InvalidArgumentException('Argument is not valid Statement entity.');
		}

		// normalize Class::method to [Class, method]
		if (is_string($entity) && Strings::contains($entity, '::') && !Strings::contains($entity, '?')) {
			$entity = explode('::', $entity);
		}
		if (is_string($entity) && substr($entity, 0, 1) === '@') { // normalize @service to Reference
			$entity = new Reference(substr($entity, 1));
		} elseif (is_array($entity) && is_string($entity[0]) && substr($entity[0], 0, 1) === '@') {
			$entity[0] = new Reference(substr($entity[0], 1));
		}

		$this->entity = $entity;
		$this->arguments = $arguments;
	}


	/** @return string|array|Definition|Reference|null */
	public function getEntity()
	{
		return $this->entity;
	}
}


class_exists(PacketeryNette\DI\Statement::class);
