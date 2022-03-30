<?php

/**
 * This file is part of the PacketeryNette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace PacketeryNette\Forms;

use PacketeryNette;


/**
 * Single validation rule or condition represented as value object.
 */
class Rule
{
	use PacketeryNette\SmartObject;

	/** @var Control */
	public $control;

	/** @var mixed */
	public $validator;

	/** @var mixed */
	public $arg;

	/** @var bool */
	public $isNegative = false;

	/** @var string|null */
	public $message;

	/** @var Rules|null  for conditions */
	public $branch;


	/** @internal */
	public function canExport(): bool
	{
		return is_string($this->validator)
			|| PacketeryNette\Utils\Callback::isStatic($this->validator);
	}
}
