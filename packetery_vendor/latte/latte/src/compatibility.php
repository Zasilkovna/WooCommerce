<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace PacketeryLatte {
	if (false) {
		/** @deprecated use PacketeryLatte\Loader */
		interface ILoader extends Loader
		{
		}
	} elseif (!interface_exists(ILoader::class)) {
		class_alias(Loader::class, ILoader::class);
	}

	if (false) {
		/** @deprecated use PacketeryLatte\Macro */
		interface IMacro extends Macro
		{
		}
	} elseif (!interface_exists(IMacro::class)) {
		class_alias(Macro::class, IMacro::class);
	}
}

namespace PacketeryLatte\Runtime {
	if (false) {
		/** @deprecated use PacketeryLatte\Runtime\HtmlStringable */
		interface IHtmlString extends HtmlStringable
		{
		}
	} elseif (!interface_exists(IHtmlString::class)) {
		class_alias(HtmlStringable::class, IHtmlString::class);
	}

	if (false) {
		/** @deprecated use PacketeryLatte\Runtime\SnippetBridge */
		interface ISnippetBridge extends SnippetBridge
		{
		}
	} elseif (!interface_exists(ISnippetBridge::class)) {
		class_alias(SnippetBridge::class, ISnippetBridge::class);
	}
}
