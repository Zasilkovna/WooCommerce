<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Packetery\Nette\DI\Extensions;

use Packetery\Nette;
/**
 * Enables registration of other extensions in $config file
 * @internal
 */
class ExtensionsExtension extends \Packetery\Nette\DI\CompilerExtension
{
    public function getConfigSchema() : \Packetery\Nette\Schema\Schema
    {
        return \Packetery\Nette\Schema\Expect::arrayOf('string|\\Packetery\\Nette\\DI\\Definitions\\Statement');
    }
    public function loadConfiguration()
    {
        foreach ($this->getConfig() as $name => $class) {
            if (\is_int($name)) {
                $name = null;
            }
            $args = [];
            if ($class instanceof \Packetery\Nette\DI\Definitions\Statement) {
                [$class, $args] = [$class->getEntity(), $class->arguments];
            }
            if (!\is_a($class, \Packetery\Nette\DI\CompilerExtension::class, \true)) {
                throw new \Packetery\Nette\DI\InvalidConfigurationException(\sprintf("Extension '%s' not found or is not \\Packetery\\Nette\\DI\\CompilerExtension descendant.", $class));
            }
            $this->compiler->addExtension($name, (new \ReflectionClass($class))->newInstanceArgs($args));
        }
    }
}
