<?php

/**
 * This file is part of the PacketeryLatte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace PacketeryLatte\Runtime;


interface HtmlStringable
{
	/** @return string in HTML format */
	function __toString(): string;
}


interface_exists(IHtmlString::class);
