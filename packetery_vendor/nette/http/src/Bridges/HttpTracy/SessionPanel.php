<?php

/**
 * This file is part of the PacketeryNette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace PacketeryNette\Bridges\HttpPacketeryTracy;

use PacketeryNette;
use PacketeryTracy;


/**
 * Session panel for Debugger Bar.
 */
class SessionPanel implements PacketeryTracy\IBarPanel
{
	use PacketeryNette\SmartObject;

	/**
	 * Renders tab.
	 */
	public function getTab(): string
	{
		return PacketeryNette\Utils\Helpers::capture(function () {
			require __DIR__ . '/templates/SessionPanel.tab.phtml';
		});
	}


	/**
	 * Renders panel.
	 */
	public function getPanel(): string
	{
		return PacketeryNette\Utils\Helpers::capture(function () {
			require __DIR__ . '/templates/SessionPanel.panel.phtml';
		});
	}
}
