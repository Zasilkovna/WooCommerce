<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Packetery\Nette\DI\Definitions;

use Packetery\Nette;
use Packetery\Nette\DI\PhpGenerator;
/**
 * Imported service injected to the container.
 * @internal
 */
final class ImportedDefinition extends Definition
{
    /** @return static */
    public function setType(?string $type)
    {
        return parent::setType($type);
    }
    public function resolveType(\Packetery\Nette\DI\Resolver $resolver) : void
    {
    }
    public function complete(\Packetery\Nette\DI\Resolver $resolver) : void
    {
    }
    public function generateMethod(\Packetery\Nette\PhpGenerator\Method $method, PhpGenerator $generator) : void
    {
        $method->setReturnType('void')->setBody('throw new \\Packetery\\Nette\\DI\\ServiceCreationException(?);', ["Unable to create imported service '{$this->getName()}', it must be added using addService()"]);
    }
    /** @deprecated use '$def instanceof ImportedDefinition' */
    public function isDynamic() : bool
    {
        \trigger_error(\sprintf('Service %s: %s() is deprecated, use "instanceof ImportedDefinition".', $this->getName(), __METHOD__), \E_USER_DEPRECATED);
        return \true;
    }
}
