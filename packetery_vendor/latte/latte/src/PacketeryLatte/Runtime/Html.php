<?php

/**
 * This file is part of the PacketeryLatte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace PacketeryLatte\Runtime;

use PacketeryLatte;


/**
 * HTML literal.
 */
class Html implements HtmlStringable
{
	use PacketeryLatte\Strict;

	/** @var string */
	private $value;


	public function __construct($value)
	{
		$this->value = (string) $value;
	}


	public function __toString(): string
	{
		return $this->value;
	}
}
