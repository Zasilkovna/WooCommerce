<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Packetery\Nette\PhpGenerator;

use Packetery\Nette;
/**
 * PHP Attribute.
 * @internal
 */
final class Attribute
{
    use \Packetery\Nette\SmartObject;
    /** @var string */
    private $name;
    /** @var array */
    private $args;
    public function __construct(string $name, array $args)
    {
        if (!Helpers::isNamespaceIdentifier($name)) {
            throw new \Packetery\Nette\InvalidArgumentException("Value '{$name}' is not valid attribute name.");
        }
        $this->name = $name;
        $this->args = $args;
    }
    public function getName() : string
    {
        return $this->name;
    }
    public function getArguments() : array
    {
        return $this->args;
    }
}
