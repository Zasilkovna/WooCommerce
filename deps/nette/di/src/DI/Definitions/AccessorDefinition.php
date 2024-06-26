<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Packetery\Nette\DI\Definitions;

use Packetery\Nette;
use Packetery\Nette\DI\ServiceCreationException;
use Packetery\Nette\Utils\Reflection;
/**
 * Accessor definition.
 * @internal
 */
final class AccessorDefinition extends Definition
{
    private const METHOD_GET = 'get';
    /** @var Reference|null */
    private $reference;
    /** @return static */
    public function setImplement(string $type)
    {
        if (!\interface_exists($type)) {
            throw new \Packetery\Nette\InvalidArgumentException("Service '{$this->getName()}': Interface '{$type}' not found.");
        }
        $rc = new \ReflectionClass($type);
        $method = $rc->getMethods()[0] ?? null;
        if (!$method || $method->isStatic() || $method->getName() !== self::METHOD_GET || \count($rc->getMethods()) > 1) {
            throw new \Packetery\Nette\InvalidArgumentException("Service '{$this->getName()}': Interface {$type} must have just one non-static method get().");
        } elseif ($method->getNumberOfParameters()) {
            throw new \Packetery\Nette\InvalidArgumentException("Service '{$this->getName()}': Method {$type}::get() must have no parameters.");
        }
        return parent::setType($type);
    }
    public function getImplement() : ?string
    {
        return $this->getType();
    }
    /**
     * @param  string|Reference  $reference
     * @return static
     */
    public function setReference($reference)
    {
        if ($reference instanceof Reference) {
            $this->reference = $reference;
        } else {
            $this->reference = \substr($reference, 0, 1) === '@' ? new Reference(\substr($reference, 1)) : Reference::fromType($reference);
        }
        return $this;
    }
    public function getReference() : ?Reference
    {
        return $this->reference;
    }
    public function resolveType(\Packetery\Nette\DI\Resolver $resolver) : void
    {
    }
    public function complete(\Packetery\Nette\DI\Resolver $resolver) : void
    {
        if (!$this->reference) {
            $interface = $this->getType();
            $method = new \ReflectionMethod($interface, self::METHOD_GET);
            $returnType = \Packetery\Nette\DI\Helpers::getReturnType($method);
            if (!$returnType) {
                throw new ServiceCreationException("Method {$interface}::get() has not return type hint or annotation @return.");
            } elseif (!\class_exists($returnType) && !\interface_exists($returnType)) {
                throw new ServiceCreationException("Check a type hint or annotation @return of the {$interface}::get() method, class '{$returnType}' cannot be found.");
            }
            $this->setReference($returnType);
        }
        $this->reference = $resolver->normalizeReference($this->reference);
    }
    public function generateMethod(\Packetery\Nette\PhpGenerator\Method $method, \Packetery\Nette\DI\PhpGenerator $generator) : void
    {
        $class = (new \Packetery\Nette\PhpGenerator\ClassType())->addImplement($this->getType());
        $class->addProperty('container')->setPrivate();
        $class->addMethod('__construct')->addBody('$this->container = $container;')->addParameter('container')->setType($generator->getClassName());
        $rm = new \ReflectionMethod($this->getType(), self::METHOD_GET);
        $class->addMethod(self::METHOD_GET)->setBody('return $this->container->getService(?);', [$this->reference->getValue()])->setReturnType(Reflection::getReturnType($rm));
        $method->setBody('return new class ($this) ' . $class . ';');
    }
}
