<?php

/**
 * This file is part of the PacketeryNette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace PacketeryNette\PhpGenerator;

use PacketeryNette;


/**
 * Class constant.
 */
final class Constant
{
	use PacketeryNette\SmartObject;
	use Traits\NameAware;
	use Traits\VisibilityAware;
	use Traits\CommentAware;
	use Traits\AttributeAware;

	/** @var mixed */
	private $value;


	/** @return static */
	public function setValue($val): self
	{
		$this->value = $val;
		return $this;
	}


	public function getValue()
	{
		return $this->value;
	}
}
