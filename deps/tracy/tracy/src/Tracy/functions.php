<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Packetery;

if (!\function_exists('Packetery\\dump')) {
    /**
     * \Packetery\Tracy\Debugger::dump() shortcut.
     * @tracySkipLocation
     * @internal
     */
    function dump($var)
    {
        \array_map([\Packetery\Tracy\Debugger::class, 'dump'], \func_get_args());
        return $var;
    }
}
if (!\function_exists('Packetery\\dumpe')) {
    /**
     * \Packetery\Tracy\Debugger::dump() & exit shortcut.
     * @tracySkipLocation
     * @internal
     */
    function dumpe($var) : void
    {
        \array_map([\Packetery\Tracy\Debugger::class, 'dump'], \func_get_args());
        if (!Tracy\Debugger::$productionMode) {
            exit;
        }
    }
}
if (!\function_exists('Packetery\\bdump')) {
    /**
     * \Packetery\Tracy\Debugger::barDump() shortcut.
     * @tracySkipLocation
     * @internal
     */
    function bdump($var)
    {
        \Packetery\Tracy\Debugger::barDump(...\func_get_args());
        return $var;
    }
}
