<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Packetery\Nette\DI\Extensions;

use Packetery\Nette;
/**
 * Constant definitions.
 * @deprecated  use \Packetery\Nette\Bootstrap\Extensions\ConstantsExtension
 * @internal
 */
final class ConstantsExtension extends \Packetery\Nette\DI\CompilerExtension
{
    public function loadConfiguration()
    {
        \trigger_error(self::class . ' is deprecated, use \\Packetery\\Nette\\Bootstrap\\Extensions\\ConstantsExtension.', \E_USER_DEPRECATED);
        foreach ($this->getConfig() as $name => $value) {
            $this->initialization->addBody('define(?, ?);', [$name, $value]);
        }
    }
}
