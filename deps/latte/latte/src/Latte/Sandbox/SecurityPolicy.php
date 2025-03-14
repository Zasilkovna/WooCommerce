<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Packetery\Latte\Sandbox;

use Packetery\Latte;
/**
 * Default-deny policy.
 */
class SecurityPolicy implements \Packetery\Latte\Policy
{
    use \Packetery\Latte\Strict;
    public const ALL = ['*'];
    /** @var string[] */
    private $tags = [];
    /** @var string[] */
    private $filters = [];
    /** @var string[] */
    private $functions = [];
    /** @var string[][] */
    private $methods = [];
    /** @var string[][] */
    private $properties = [];
    /** @var array<string, array<string, bool>> */
    private $methodCache = [];
    /** @var array<string, array<string, bool>> */
    private $propertyCache = [];
    public static function createSafePolicy() : self
    {
        $policy = new self();
        // does not include: contentType, debugbreak, dump, extends, import, include, includeblock, layout,
        // php (but 'do' is allowed), sandbox, snippet, snippetArea, templatePrint, varPrint, embed
        $policy->allowTags(['_', '=', 'attr', 'block', 'breakIf', 'capture', 'case', 'class', 'continueIf', 'default', 'define', 'do', 'else', 'elseif', 'elseifset', 'first', 'for', 'foreach', 'if', 'ifchanged', 'ifcontent', 'iterateWhile', 'ifset', 'l', 'last', 'r', 'rollback', 'sep', 'skipIf', 'spaceless', 'switch', 'templateType', 'translate', 'try', 'var', 'varType', 'while']);
        // does not include: dataStream, noEscape, noCheck
        $policy->allowFilters(['batch', 'breaklines', 'breakLines', 'bytes', 'capitalize', 'ceil', 'clamp', 'date', 'escapeCss', 'escapeHtml', 'escapeHtmlComment', 'escapeICal', 'escapeJs', 'escapeUrl', 'escapeXml', 'explode', 'first', 'firstUpper', 'floor', 'checkUrl', 'implode', 'indent', 'join', 'last', 'length', 'lower', 'number', 'padLeft', 'padRight', 'query', 'random', 'repeat', 'replace', 'replaceRe', 'reverse', 'round', 'slice', 'sort', 'spaceless', 'split', 'strip', 'striphtml', 'stripHtml', 'striptags', 'stripTags', 'substr', 'trim', 'truncate', 'upper', 'webalize']);
        $policy->allowFunctions(['clamp', 'divisibleBy', 'even', 'first', 'last', 'odd', 'slice']);
        $policy->allowMethods(\Packetery\Latte\Runtime\CachingIterator::class, self::ALL);
        $policy->allowProperties(\Packetery\Latte\Runtime\CachingIterator::class, self::ALL);
        return $policy;
    }
    /**
     * @deprecated  use allowTags()
     */
    public function allowMacros(array $tags) : self
    {
        \trigger_error(__METHOD__ . '() is deprecated, use allowTags()', \E_USER_DEPRECATED);
        $this->allowTags($tags);
        return $this;
    }
    /**
     * @param  string[]  $tags
     */
    public function allowTags(array $tags) : self
    {
        $this->tags += \array_flip(\array_map('strtolower', $tags));
        return $this;
    }
    /**
     * @param  string[]  $filters
     */
    public function allowFilters(array $filters) : self
    {
        $this->filters += \array_flip(\array_map('strtolower', $filters));
        return $this;
    }
    /**
     * @param  string[]  $functions
     */
    public function allowFunctions(array $functions) : self
    {
        $this->functions += \array_flip(\array_map('strtolower', $functions));
        return $this;
    }
    /**
     * @param  string[]  $methods
     */
    public function allowMethods(string $class, array $methods) : self
    {
        $this->methodCache = [];
        $this->methods[$class] = \array_flip(\array_map('strtolower', $methods));
        return $this;
    }
    /**
     * @param  string[]  $properties
     */
    public function allowProperties(string $class, array $properties) : self
    {
        $this->propertyCache = [];
        $this->properties[$class] = \array_flip(\array_map('strtolower', $properties));
        return $this;
    }
    public function isMacroAllowed(string $macro) : bool
    {
        return isset($this->tags[\strtolower($macro)]) || isset($this->tags['*']);
    }
    public function isFilterAllowed(string $filter) : bool
    {
        return isset($this->filters[\strtolower($filter)]) || isset($this->filters['*']);
    }
    public function isFunctionAllowed(string $function) : bool
    {
        return isset($this->functions[\strtolower($function)]) || isset($this->functions['*']);
    }
    public function isMethodAllowed(string $class, string $method) : bool
    {
        $method = \strtolower($method);
        $res =& $this->methodCache[$class][$method];
        \assert(\is_bool($res) || $res === null);
        if (isset($res)) {
            return $res;
        }
        foreach ($this->methods as $c => $methods) {
            if (\is_a($class, $c, \true) && (isset($methods[$method]) || isset($methods['*']))) {
                return $res = \true;
            }
        }
        return $res = \false;
    }
    public function isPropertyAllowed(string $class, string $property) : bool
    {
        $property = \strtolower($property);
        $res =& $this->propertyCache[$class][$property];
        \assert(\is_bool($res) || $res === null);
        if (isset($res)) {
            return $res;
        }
        foreach ($this->properties as $c => $properties) {
            if (\is_a($class, $c, \true) && (isset($properties[$property]) || isset($properties['*']))) {
                return $res = \true;
            }
        }
        return $res = \false;
    }
}
