<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Packetery\Latte\Runtime;

use Packetery\Latte;
use Packetery\Latte\Engine;
use Packetery\Latte\Helpers;
/**
 * Filter executor.
 * @internal
 */
#[\AllowDynamicProperties]
class FilterExecutor
{
    /** @var string[] */
    public $_origNames = [];
    /** @var callable[] */
    private $_dynamic = [];
    /** @var array<string, array{callable, ?bool}> */
    private $_static = [];
    /**
     * Registers run-time filter.
     * @return static
     */
    public function add(?string $name, callable $callback)
    {
        if ($name === null) {
            \array_unshift($this->_dynamic, $callback);
        } else {
            $lower = \strtolower($name);
            $this->_static[$lower] = [$callback, null, $name];
            $this->_origNames[$name] = $lower;
            unset($this->{$lower});
        }
        return $this;
    }
    /**
     * Returns all run-time filters.
     * @return string[]
     */
    public function getAll() : array
    {
        return \array_combine($tmp = \array_keys($this->_static), $tmp);
    }
    /**
     * Returns filter for classic calling.
     */
    public function __get(string $name) : callable
    {
        $lname = \strtolower($name);
        if (isset($this->{$lname})) {
            // case mismatch
            return $this->{$lname};
        } elseif (isset($this->_static[$lname])) {
            [$callback, $aware] = $this->prepareFilter($lname);
            if ($aware) {
                // FilterInfo aware filter
                return $this->{$lname} = function (...$args) use($callback) {
                    \array_unshift($args, $info = new FilterInfo());
                    if ($args[1] instanceof HtmlStringable) {
                        $args[1] = $args[1]->__toString();
                        $info->contentType = Engine::CONTENT_HTML;
                    }
                    $res = $callback(...$args);
                    return $info->contentType === Engine::CONTENT_HTML ? new Html($res) : $res;
                };
            } else {
                // classic filter
                return $this->{$lname} = $callback;
            }
        }
        return $this->{$lname} = function (...$args) use($lname, $name) {
            // dynamic filter
            \array_unshift($args, $name);
            foreach ($this->_dynamic as $filter) {
                $res = $filter(...$args);
                if ($res !== null) {
                    return $res;
                } elseif (isset($this->_static[$lname])) {
                    // dynamic converted to classic
                    $this->{$name} = $this->_static[$lname][0];
                    return ($this->{$name})(...\func_get_args());
                }
            }
            $hint = ($t = Helpers::getSuggestion(\array_keys($this->_static), $name)) ? ", did you mean '{$t}'?" : '.';
            throw new \LogicException("Filter '{$name}' is not defined{$hint}");
        };
    }
    /**
     * Calls filter with FilterInfo.
     * @param  mixed  ...$args
     * @return mixed
     */
    public function filterContent(string $name, FilterInfo $info, ...$args)
    {
        $lname = \strtolower($name);
        if (!isset($this->_static[$lname])) {
            $hint = ($t = Helpers::getSuggestion(\array_keys($this->_static), $name)) ? ", did you mean '{$t}'?" : '.';
            throw new \LogicException("Filter |{$name} is not defined{$hint}");
        }
        [$callback, $aware] = $this->prepareFilter($lname);
        if ($info->contentType === Engine::CONTENT_HTML && $args[0] instanceof HtmlStringable) {
            $args[0] = $args[0]->__toString();
        }
        if ($aware) {
            // FilterInfo aware filter
            \array_unshift($args, $info);
            return $callback(...$args);
        }
        // classic filter
        if ($info->contentType !== Engine::CONTENT_TEXT) {
            throw new \Packetery\Latte\RuntimeException("Filter |{$name} is called with incompatible content type " . \strtoupper($info->contentType) . ($info->contentType === Engine::CONTENT_HTML ? ', try to prepend |stripHtml.' : '.'));
        }
        $res = ($this->{$name})(...$args);
        if ($res instanceof HtmlStringable) {
            \trigger_error("Filter |{$name} should be changed to content-aware filter.");
            $info->contentType = Engine::CONTENT_HTML;
            $res = $res->__toString();
        }
        return $res;
    }
    /**
     * @return array{callable, bool}
     */
    private function prepareFilter(string $name) : array
    {
        if (isset($this->_static[$name][1])) {
            return $this->_static[$name];
        }
        $callback = $this->_static[$name][0];
        if (\is_string($callback) && \strpos($callback, '::')) {
            $callback = \explode('::', $callback);
        } elseif (\is_object($callback)) {
            $callback = [$callback, '__invoke'];
        }
        $ref = \is_array($callback) ? new \ReflectionMethod($callback[0], $callback[1]) : new \ReflectionFunction($callback);
        $this->_static[$name][1] = ($tmp = $ref->getParameters()) && $tmp[0]->getType() instanceof \ReflectionNamedType && $tmp[0]->getType()->getName() === FilterInfo::class;
        return $this->_static[$name];
    }
}
