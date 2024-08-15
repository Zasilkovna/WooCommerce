<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Packetery\Nette\DI\Definitions;

use Packetery\Nette;
/**
 * Definition used by ContainerBuilder.
 * @internal
 */
abstract class Definition
{
    use \Packetery\Nette\SmartObject;
    /** @var string|null */
    private $name;
    /** @var string|null  class or interface name */
    private $type;
    /** @var array */
    private $tags = [];
    /** @var bool|string[] */
    private $autowired = \true;
    /** @var callable|null */
    private $notifier;
    /**
     * @return static
     * @internal  This is managed by ContainerBuilder and should not be called by user
     */
    public final function setName(string $name)
    {
        if ($this->name) {
            throw new \Packetery\Nette\InvalidStateException('Name already has been set.');
        }
        $this->name = $name;
        return $this;
    }
    public final function getName() : ?string
    {
        return $this->name;
    }
    /** @return static */
    protected function setType(?string $type)
    {
        if ($this->autowired && $this->notifier && $this->type !== $type) {
            ($this->notifier)();
        }
        if ($type === null) {
            $this->type = null;
        } elseif (!\class_exists($type) && !\interface_exists($type)) {
            throw new \Packetery\Nette\InvalidArgumentException(\sprintf("Service '%s': Class or interface '%s' not found.", $this->name, $type));
        } else {
            $this->type = \Packetery\Nette\DI\Helpers::normalizeClass($type);
        }
        return $this;
    }
    public final function getType() : ?string
    {
        return $this->type;
    }
    /** @return static */
    public final function setTags(array $tags)
    {
        $this->tags = $tags;
        return $this;
    }
    public final function getTags() : array
    {
        return $this->tags;
    }
    /**
     * @param  mixed  $attr
     * @return static
     */
    public final function addTag(string $tag, $attr = \true)
    {
        $this->tags[$tag] = $attr;
        return $this;
    }
    /** @return mixed */
    public final function getTag(string $tag)
    {
        return $this->tags[$tag] ?? null;
    }
    /**
     * @param  bool|string|string[]  $state
     * @return static
     */
    public final function setAutowired($state = \true)
    {
        if ($this->notifier && $this->autowired !== $state) {
            ($this->notifier)();
        }
        $this->autowired = \is_string($state) || \is_array($state) ? (array) $state : (bool) $state;
        return $this;
    }
    /** @return bool|string[] */
    public final function getAutowired()
    {
        return $this->autowired;
    }
    /** @return static */
    public function setExported(bool $state = \true)
    {
        return $this->addTag('nette.exported', $state);
    }
    public function isExported() : bool
    {
        return (bool) $this->getTag('nette.exported');
    }
    public function __clone()
    {
        $this->notifier = $this->name = null;
    }
    /********************* life cycle ****************d*g**/
    public abstract function resolveType(\Packetery\Nette\DI\Resolver $resolver) : void;
    public abstract function complete(\Packetery\Nette\DI\Resolver $resolver) : void;
    public abstract function generateMethod(\Packetery\Nette\PhpGenerator\Method $method, \Packetery\Nette\DI\PhpGenerator $generator) : void;
    public final function setNotifier(?callable $notifier) : void
    {
        $this->notifier = $notifier;
    }
    /********************* deprecated stuff from former ServiceDefinition ****************d*g**/
    /** @deprecated Use setType() */
    public function setClass(?string $type)
    {
        return $this->setType($type);
    }
    /** @deprecated Use getType() */
    public function getClass() : ?string
    {
        return $this->getType();
    }
    /** @deprecated Use '$def instanceof \Packetery\Nette\DI\Definitions\ImportedDefinition' */
    public function isDynamic() : bool
    {
        \trigger_error(\sprintf('Service %s: %s() is deprecated, use "instanceof ImportedDefinition".', $this->getName(), __METHOD__), \E_USER_DEPRECATED);
        return \false;
    }
    /** @deprecated Use \Packetery\Nette\DI\Definitions\FactoryDefinition or AccessorDefinition */
    public function getImplement() : ?string
    {
        \trigger_error(\sprintf('Service %s: %s() is deprecated.', $this->getName(), __METHOD__), \E_USER_DEPRECATED);
        return null;
    }
    /** @deprecated Use getAutowired() */
    public function isAutowired()
    {
        return $this->autowired;
    }
}
