<?php

/**
 * This file is part of the PacketeryTracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

if (!function_exists('dump')) {
	/**
	 * PacketeryTracy\Debugger::dump() shortcut.
	 * @tracySkipLocation
	 */
	function dump($var)
	{
		array_map([PacketeryTracy\Debugger::class, 'dump'], func_get_args());
		return $var;
	}
}

if (!function_exists('dumpe')) {
	/**
	 * PacketeryTracy\Debugger::dump() & exit shortcut.
	 * @tracySkipLocation
	 */
	function dumpe($var): void
	{
		array_map([PacketeryTracy\Debugger::class, 'dump'], func_get_args());
		if (!PacketeryTracy\Debugger::$productionMode) {
			exit;
		}
	}
}

if (!function_exists('bdump')) {
	/**
	 * PacketeryTracy\Debugger::barDump() shortcut.
	 * @tracySkipLocation
	 */
	function bdump($var)
	{
		PacketeryTracy\Debugger::barDump(...func_get_args());
		return $var;
	}
}
