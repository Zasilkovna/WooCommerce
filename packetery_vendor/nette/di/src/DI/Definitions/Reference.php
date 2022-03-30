<?php

/**
 * This file is part of the PacketeryNette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace PacketeryNette\DI\Definitions;

use PacketeryNette;


/**
 * Reference to service. Either by name or by type or reference to the 'self' service.
 */
final class Reference
{
	use PacketeryNette\SmartObject;

	public const SELF = 'self';

	/** @var string */
	private $value;


	public static function fromType(string $value): self
	{
		if (strpos($value, '\\') === false) {
			$value = '\\' . $value;
		}
		return new static($value);
	}


	public function __construct(string $value)
	{
		$this->value = $value;
	}


	public function getValue(): string
	{
		return $this->value;
	}


	public function isName(): bool
	{
		return strpos($this->value, '\\') === false && $this->value !== self::SELF;
	}


	public function isType(): bool
	{
		return strpos($this->value, '\\') !== false;
	}


	public function isSelf(): bool
	{
		return $this->value === self::SELF;
	}
}
