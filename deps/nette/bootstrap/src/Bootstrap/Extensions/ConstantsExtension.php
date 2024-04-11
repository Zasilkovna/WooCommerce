<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Packetery\Nette\Bootstrap\Extensions;

use Packetery\Nette;
/**
 * Constant definitions.
 * @internal
 */
final class ConstantsExtension extends \Packetery\Nette\DI\CompilerExtension
{
    public function loadConfiguration()
    {
        foreach ($this->getConfig() as $name => $value) {
            $this->initialization->addBody('define(?, ?);', [$name, $value]);
        }
    }
}
