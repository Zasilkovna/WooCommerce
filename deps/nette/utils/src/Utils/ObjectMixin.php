<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Packetery\Nette\Utils;

use Packetery\Nette;
/**
 * \Packetery\Nette\Object behaviour mixin.
 * @deprecated
 * @internal
 */
final class ObjectMixin
{
    use \Packetery\Nette\StaticClass;
    /** @deprecated  use ObjectHelpers::getSuggestion() */
    public static function getSuggestion(array $possibilities, string $value) : ?string
    {
        \trigger_error(__METHOD__ . '() has been renamed to \\Packetery\\Nette\\Utils\\ObjectHelpers::getSuggestion()', \E_USER_DEPRECATED);
        return ObjectHelpers::getSuggestion($possibilities, $value);
    }
    public static function setExtensionMethod() : void
    {
        \trigger_error('Class \\Packetery\\Nette\\Utils\\ObjectMixin is deprecated', \E_USER_DEPRECATED);
    }
    public static function getExtensionMethod() : void
    {
        \trigger_error('Class \\Packetery\\Nette\\Utils\\ObjectMixin is deprecated', \E_USER_DEPRECATED);
    }
}
