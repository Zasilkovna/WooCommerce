<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Packetery\Nette\DI\Config;

use Packetery\Nette;
/**
 * Configuration helpers.
 * @deprecated
 * @internal
 */
final class Helpers
{
    use \Packetery\Nette\StaticClass;
    public const PREVENT_MERGING = '_prevent_merging';
    /** @deprecated */
    public static function merge($left, $right)
    {
        return \Packetery\Nette\Schema\Helpers::merge($left, $right);
    }
    /** @deprecated */
    public static function takeParent(&$data) : bool
    {
        if (\is_array($data) && isset($data[self::PREVENT_MERGING])) {
            unset($data[self::PREVENT_MERGING]);
            return \true;
        }
        return \false;
    }
}
