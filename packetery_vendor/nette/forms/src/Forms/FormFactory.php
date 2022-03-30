<?php

/**
 * This file is part of the PacketeryNette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace PacketeryNette\Forms;

use PacketeryNette;


/**
 * Creates form.
 */
final class FormFactory
{
	use PacketeryNette\StaticClass;

	/** @var PacketeryNette\Http\IRequest */
	private $httpRequest;


	public function __construct(PacketeryNette\Http\IRequest $httpRequest)
	{
		$this->httpRequest = $httpRequest;
	}


	public function createForm(string $name = null): Form
	{
		$form = new Form($name);
		$form->setHttpRequest($this->httpRequest);
		return $form;
	}
}
