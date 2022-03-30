<?php

/**
 * This file is part of the PacketeryNette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace PacketeryNette\PhpGenerator;

use PacketeryNette;


/**
 * Global function.
 *
 * @property string $body
 */
final class GlobalFunction
{
	use PacketeryNette\SmartObject;
	use Traits\FunctionLike;
	use Traits\NameAware;
	use Traits\CommentAware;
	use Traits\AttributeAware;

	public static function from(string $function): self
	{
		return (new Factory)->fromFunctionReflection(new \ReflectionFunction($function));
	}


	public static function withBodyFrom(string $function): self
	{
		return (new Factory)->fromFunctionReflection(new \ReflectionFunction($function), true);
	}


	public function __toString(): string
	{
		try {
			return (new Printer)->printFunction($this);
		} catch (\Throwable $e) {
			if (PHP_VERSION_ID >= 70400) {
				throw $e;
			}
			trigger_error('Exception in ' . __METHOD__ . "(): {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}", E_USER_ERROR);
			return '';
		}
	}
}