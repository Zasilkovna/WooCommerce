<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Packetery\Nette\DI\Config;

if (\false) {
    /** @deprecated use \Packetery\Nette\DI\Config\Adapter
     * @internal
     */
    interface IAdapter
    {
    }
} elseif (!\interface_exists(IAdapter::class)) {
    \class_alias(Adapter::class, IAdapter::class);
}
namespace Packetery\Nette\DI;

if (\false) {
    /** @deprecated use \Packetery\Nette\DI\Definitions\ServiceDefinition
     * @internal
     */
    class ServiceDefinition
    {
    }
} elseif (!\class_exists(ServiceDefinition::class)) {
    \class_alias(Definitions\ServiceDefinition::class, ServiceDefinition::class);
}
if (\false) {
    /** @deprecated use \Packetery\Nette\DI\Definitions\Statement
     * @internal
     */
    class Statement
    {
    }
} elseif (!\class_exists(Statement::class)) {
    \class_alias(Definitions\Statement::class, Statement::class);
}
