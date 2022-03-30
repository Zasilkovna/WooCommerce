<?php

/**
 * This file is part of the PacketeryNette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace PacketeryNette\Utils;

use PacketeryNette;

if (false) {
	/** @deprecated use PacketeryNette\HtmlStringable */
	interface IHtmlString extends PacketeryNette\HtmlStringable
	{
	}
} elseif (!interface_exists(IHtmlString::class)) {
	class_alias(PacketeryNette\HtmlStringable::class, IHtmlString::class);
}

namespace PacketeryNette\Localization;

if (false) {
	/** @deprecated use PacketeryNette\Localization\Translator */
	interface ITranslator extends Translator
	{
	}
} elseif (!interface_exists(ITranslator::class)) {
	class_alias(Translator::class, ITranslator::class);
}
