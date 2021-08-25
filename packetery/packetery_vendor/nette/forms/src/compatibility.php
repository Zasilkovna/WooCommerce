<?php

/**
 * This file is part of the PacketeryNette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace PacketeryNette\Forms;

if (false) {
	/** @deprecated use PacketeryNette\Forms\Control */
	class IControl extends Control
	{
	}
} elseif (!interface_exists(IControl::class)) {
	class_alias(Control::class, IControl::class);
}

if (false) {
	/** @deprecated use PacketeryNette\Forms\FormRenderer */
	class IFormRenderer extends FormRenderer
	{
	}
} elseif (!interface_exists(IFormRenderer::class)) {
	class_alias(FormRenderer::class, IFormRenderer::class);
}

if (false) {
	/** @deprecated use PacketeryNette\Forms\SubmitterControl */
	class ISubmitterControl extends SubmitterControl
	{
	}
} elseif (!interface_exists(ISubmitterControl::class)) {
	class_alias(SubmitterControl::class, ISubmitterControl::class);
}
