<?php

/**
 * This file is part of the PacketeryNette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace PacketeryNette;


interface HtmlStringable
{
	/**
	 * Returns string in HTML format
	 */
	function __toString(): string;
}


interface_exists(Utils\IHtmlString::class);
