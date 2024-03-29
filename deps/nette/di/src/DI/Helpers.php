<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Packetery\Nette\DI;

use Packetery\Nette;
use Packetery\Nette\DI\Definitions\Reference;
use Packetery\Nette\DI\Definitions\Statement;
use Packetery\Nette\Utils\Reflection;
/**
 * The DI helpers.
 * @internal
 */
final class Helpers
{
    use \Packetery\Nette\StaticClass;
    /**
     * Expands %placeholders%.
     * @param  mixed  $var
     * @param  bool|array  $recursive
     * @return mixed
     * @throws \Packetery\Nette\InvalidArgumentException
     */
    public static function expand($var, array $params, $recursive = \false)
    {
        if (\is_array($var)) {
            $res = [];
            foreach ($var as $key => $val) {
                $res[self::expand($key, $params, $recursive)] = self::expand($val, $params, $recursive);
            }
            return $res;
        } elseif ($var instanceof Statement) {
            return new Statement(self::expand($var->getEntity(), $params, $recursive), self::expand($var->arguments, $params, $recursive));
        } elseif ($var === '%parameters%' && !\array_key_exists('parameters', $params)) {
            return $recursive ? self::expand($params, $params, \is_array($recursive) ? $recursive : []) : $params;
        } elseif (!\is_string($var)) {
            return $var;
        }
        $parts = \preg_split('#%([\\w.-]*)%#i', $var, -1, \PREG_SPLIT_DELIM_CAPTURE);
        $res = [];
        $php = \false;
        foreach ($parts as $n => $part) {
            if ($n % 2 === 0) {
                $res[] = $part;
            } elseif ($part === '') {
                $res[] = '%';
            } elseif (isset($recursive[$part])) {
                throw new \Packetery\Nette\InvalidArgumentException(\sprintf('Circular reference detected for variables: %s.', \implode(', ', \array_keys($recursive))));
            } else {
                $val = $params;
                foreach (\explode('.', $part) as $key) {
                    if (\is_array($val) && \array_key_exists($key, $val)) {
                        $val = $val[$key];
                    } elseif ($val instanceof DynamicParameter) {
                        $val = new DynamicParameter($val . '[' . \var_export($key, \true) . ']');
                    } else {
                        throw new \Packetery\Nette\InvalidArgumentException("Missing parameter '{$part}'.");
                    }
                }
                if ($recursive) {
                    $val = self::expand($val, $params, (\is_array($recursive) ? $recursive : []) + [$part => 1]);
                }
                if (\strlen($part) + 2 === \strlen($var)) {
                    return $val;
                }
                if ($val instanceof DynamicParameter) {
                    $php = \true;
                } elseif (!\is_scalar($val)) {
                    throw new \Packetery\Nette\InvalidArgumentException("Unable to concatenate non-scalar parameter '{$part}' into '{$var}'.");
                }
                $res[] = $val;
            }
        }
        if ($php) {
            $res = \array_filter($res, function ($val) : bool {
                return $val !== '';
            });
            $res = \array_map(function ($val) : string {
                return $val instanceof DynamicParameter ? "({$val})" : \var_export((string) $val, \true);
            }, $res);
            return new DynamicParameter(\implode(' . ', $res));
        }
        return \implode('', $res);
    }
    /**
     * Escapes '%' and '@'
     * @param  mixed  $value
     * @return mixed
     */
    public static function escape($value)
    {
        if (\is_array($value)) {
            $res = [];
            foreach ($value as $key => $val) {
                $key = \is_string($key) ? \str_replace('%', '%%', $key) : $key;
                $res[$key] = self::escape($val);
            }
            return $res;
        } elseif (\is_string($value)) {
            return \preg_replace('#^@|%#', '$0$0', $value);
        }
        return $value;
    }
    /**
     * Removes ... and process constants recursively.
     */
    public static function filterArguments(array $args) : array
    {
        foreach ($args as $k => $v) {
            if ($v === '...') {
                unset($args[$k]);
            } elseif (\is_string($v) && \preg_match('#^[\\w\\\\]*::[A-Z][A-Z0-9_]*$#D', $v, $m)) {
                $args[$k] = \constant(\ltrim($v, ':'));
            } elseif (\is_string($v) && \preg_match('#^@[\\w\\\\]+$#D', $v)) {
                $args[$k] = new Reference(\substr($v, 1));
            } elseif (\is_array($v)) {
                $args[$k] = self::filterArguments($v);
            } elseif ($v instanceof Statement) {
                [$tmp] = self::filterArguments([$v->getEntity()]);
                $args[$k] = new Statement($tmp, self::filterArguments($v->arguments));
            }
        }
        return $args;
    }
    /**
     * Replaces @extension with real extension name in service definition.
     * @param  mixed  $config
     * @return mixed
     */
    public static function prefixServiceName($config, string $namespace)
    {
        if (\is_string($config)) {
            if (\strncmp($config, '@extension.', 10) === 0) {
                $config = '@' . $namespace . '.' . \substr($config, 11);
            }
        } elseif ($config instanceof Reference) {
            if (\strncmp($config->getValue(), 'extension.', 9) === 0) {
                $config = new Reference($namespace . '.' . \substr($config->getValue(), 10));
            }
        } elseif ($config instanceof Statement) {
            return new Statement(self::prefixServiceName($config->getEntity(), $namespace), self::prefixServiceName($config->arguments, $namespace));
        } elseif (\is_array($config)) {
            foreach ($config as &$val) {
                $val = self::prefixServiceName($val, $namespace);
            }
        }
        return $config;
    }
    /**
     * Returns an annotation value.
     * @param  \ReflectionFunctionAbstract|\ReflectionProperty|\ReflectionClass  $ref
     */
    public static function parseAnnotation(\Reflector $ref, string $name) : ?string
    {
        if (!Reflection::areCommentsAvailable()) {
            throw new \Packetery\Nette\InvalidStateException('You have to enable phpDoc comments in opcode cache.');
        }
        $re = '#[\\s*]@' . \preg_quote($name, '#') . '(?=\\s|$)(?:[ \\t]+([^@\\s]\\S*))?#';
        if ($ref->getDocComment() && \preg_match($re, \trim($ref->getDocComment(), '/*'), $m)) {
            return $m[1] ?? '';
        }
        return null;
    }
    public static function getReturnType(\ReflectionFunctionAbstract $func) : ?string
    {
        if ($type = Reflection::getReturnType($func)) {
            return $type;
        } elseif ($type = \preg_replace('#[|\\s].*#', '', (string) self::parseAnnotation($func, 'return'))) {
            if ($type === 'object' || $type === 'mixed') {
                return null;
            } elseif ($func instanceof \ReflectionMethod) {
                return $type === 'static' || $type === '$this' ? $func->getDeclaringClass()->name : Reflection::expandClassName($type, $func->getDeclaringClass());
            } else {
                return $type;
            }
        }
        return null;
    }
    public static function normalizeClass(string $type) : string
    {
        return \class_exists($type) || \interface_exists($type) ? (new \ReflectionClass($type))->name : $type;
    }
    /**
     * Non data-loss type conversion.
     * @param  mixed  $value
     * @return mixed
     * @throws \Packetery\Nette\InvalidStateException
     */
    public static function convertType($value, string $type)
    {
        if (\is_scalar($value)) {
            $norm = $value === \false ? '0' : (string) $value;
            if ($type === 'float') {
                $norm = \preg_replace('#\\.0*$#D', '', $norm);
            }
            $orig = $norm;
            \settype($norm, $type);
            if ($orig === ($norm === \false ? '0' : (string) $norm)) {
                return $norm;
            }
        }
        $value = \is_scalar($value) ? "'{$value}'" : \gettype($value);
        throw new \Packetery\Nette\InvalidStateException("Cannot convert {$value} to {$type}.");
    }
}
