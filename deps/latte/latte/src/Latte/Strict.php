<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Packetery\Latte;

use LogicException;
/**
 * Better OOP experience.
 * @internal
 */
trait Strict
{
    /**
     * Call to undefined method.
     * @param  mixed[]  $args
     * @return mixed
     * @throws LogicException
     */
    public function __call(string $name, array $args)
    {
        $class = \method_exists($this, $name) ? 'parent' : static::class;
        $items = (new \ReflectionClass($this))->getMethods(\ReflectionMethod::IS_PUBLIC);
        $items = \array_map(function ($item) {
            return $item->getName();
        }, $items);
        $hint = ($t = Helpers::getSuggestion($items, $name)) ? ", did you mean {$t}()?" : '.';
        throw new LogicException("Call to undefined method {$class}::{$name}(){$hint}");
    }
    /**
     * Call to undefined static method.
     * @param  mixed[]  $args
     * @return mixed
     * @throws LogicException
     */
    public static function __callStatic(string $name, array $args)
    {
        $rc = new \ReflectionClass(static::class);
        $items = \array_filter($rc->getMethods(\ReflectionMethod::IS_STATIC), function ($m) {
            return $m->isPublic();
        });
        $items = \array_map(function ($item) {
            return $item->getName();
        }, $items);
        $hint = ($t = Helpers::getSuggestion($items, $name)) ? ", did you mean {$t}()?" : '.';
        throw new LogicException("Call to undefined static method {$rc->name}::{$name}(){$hint}");
    }
    /**
     * Access to undeclared property.
     * @return mixed
     * @throws LogicException
     */
    public function &__get(string $name)
    {
        $rc = new \ReflectionClass($this);
        $items = \array_filter($rc->getProperties(\ReflectionProperty::IS_PUBLIC), function ($p) {
            return !$p->isStatic();
        });
        $items = \array_map(function ($item) {
            return $item->getName();
        }, $items);
        $hint = ($t = Helpers::getSuggestion($items, $name)) ? ", did you mean \${$t}?" : '.';
        throw new LogicException("Attempt to read undeclared property {$rc->name}::\${$name}{$hint}");
    }
    /**
     * Access to undeclared property.
     * @param  mixed  $value
     * @throws LogicException
     */
    public function __set(string $name, $value) : void
    {
        $rc = new \ReflectionClass($this);
        $items = \array_filter($rc->getProperties(\ReflectionProperty::IS_PUBLIC), function ($p) {
            return !$p->isStatic();
        });
        $items = \array_map(function ($item) {
            return $item->getName();
        }, $items);
        $hint = ($t = Helpers::getSuggestion($items, $name)) ? ", did you mean \${$t}?" : '.';
        throw new LogicException("Attempt to write to undeclared property {$rc->name}::\${$name}{$hint}");
    }
    public function __isset(string $name) : bool
    {
        return \false;
    }
    /**
     * Access to undeclared property.
     * @throws LogicException
     */
    public function __unset(string $name) : void
    {
        $class = static::class;
        throw new LogicException("Attempt to unset undeclared property {$class}::\${$name}.");
    }
}
