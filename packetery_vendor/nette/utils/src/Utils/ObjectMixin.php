<?php

/**
 * This file is part of the PacketeryNette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace PacketeryNette\Utils;

use PacketeryNette;


/**
 * PacketeryNette\Object behaviour mixin.
 * @deprecated
 */
final class ObjectMixin
{
	use PacketeryNette\StaticClass;

	/** @deprecated  use ObjectHelpers::getSuggestion() */
	public static function getSuggestion(array $possibilities, string $value): ?string
	{
		trigger_error(__METHOD__ . '() has been renamed to PacketeryNette\Utils\ObjectHelpers::getSuggestion()', E_USER_DEPRECATED);
		return ObjectHelpers::getSuggestion($possibilities, $value);
	}


	public static function setExtensionMethod(): void
	{
		trigger_error('Class PacketeryNette\Utils\ObjectMixin is deprecated', E_USER_DEPRECATED);
	}


	public static function getExtensionMethod(): void
	{
		trigger_error('Class PacketeryNette\Utils\ObjectMixin is deprecated', E_USER_DEPRECATED);
	}
}
