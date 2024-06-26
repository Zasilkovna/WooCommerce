<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Packetery\Latte;

/** @internal */
interface Policy
{
    function isMacroAllowed(string $macro) : bool;
    function isFilterAllowed(string $filter) : bool;
    function isFunctionAllowed(string $function) : bool;
    function isMethodAllowed(string $class, string $method) : bool;
    function isPropertyAllowed(string $class, string $property) : bool;
}
