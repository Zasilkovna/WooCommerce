<?php

namespace Packetery;

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Packetery\Symfony\Polyfill\Intl\Grapheme as p;
if (!\defined('GRAPHEME_EXTR_COUNT')) {
    \define('GRAPHEME_EXTR_COUNT', 0);
}
if (!\defined('GRAPHEME_EXTR_MAXBYTES')) {
    \define('GRAPHEME_EXTR_MAXBYTES', 1);
}
if (!\defined('GRAPHEME_EXTR_MAXCHARS')) {
    \define('GRAPHEME_EXTR_MAXCHARS', 2);
}
if (!\function_exists('grapheme_extract')) {
    /** @internal */
    function grapheme_extract(?string $haystack, ?int $size, ?int $type = \GRAPHEME_EXTR_COUNT, ?int $offset = 0, &$next = null) : string|false
    {
        return p\Grapheme::grapheme_extract((string) $haystack, (int) $size, (int) $type, (int) $offset, $next);
    }
}
if (!\function_exists('grapheme_stripos')) {
    /** @internal */
    function grapheme_stripos(?string $haystack, ?string $needle, ?int $offset = 0) : int|false
    {
        return p\Grapheme::grapheme_stripos((string) $haystack, (string) $needle, (int) $offset);
    }
}
if (!\function_exists('grapheme_stristr')) {
    /** @internal */
    function grapheme_stristr(?string $haystack, ?string $needle, ?bool $beforeNeedle = \false) : string|false
    {
        return p\Grapheme::grapheme_stristr((string) $haystack, (string) $needle, (bool) $beforeNeedle);
    }
}
if (!\function_exists('grapheme_strlen')) {
    /** @internal */
    function grapheme_strlen(?string $string) : int|false|null
    {
        return p\Grapheme::grapheme_strlen((string) $string);
    }
}
if (!\function_exists('grapheme_strpos')) {
    /** @internal */
    function grapheme_strpos(?string $haystack, ?string $needle, ?int $offset = 0) : int|false
    {
        return p\Grapheme::grapheme_strpos((string) $haystack, (string) $needle, (int) $offset);
    }
}
if (!\function_exists('grapheme_strripos')) {
    /** @internal */
    function grapheme_strripos(?string $haystack, ?string $needle, ?int $offset = 0) : int|false
    {
        return p\Grapheme::grapheme_strripos((string) $haystack, (string) $needle, (int) $offset);
    }
}
if (!\function_exists('grapheme_strrpos')) {
    /** @internal */
    function grapheme_strrpos(?string $haystack, ?string $needle, ?int $offset = 0) : int|false
    {
        return p\Grapheme::grapheme_strrpos((string) $haystack, (string) $needle, (int) $offset);
    }
}
if (!\function_exists('grapheme_strstr')) {
    /** @internal */
    function grapheme_strstr(?string $haystack, ?string $needle, ?bool $beforeNeedle = \false) : string|false
    {
        return p\Grapheme::grapheme_strstr((string) $haystack, (string) $needle, (bool) $beforeNeedle);
    }
}
if (!\function_exists('grapheme_substr')) {
    /** @internal */
    function grapheme_substr(?string $string, ?int $offset, ?int $length = null) : string|false
    {
        return p\Grapheme::grapheme_substr((string) $string, (int) $offset, $length);
    }
}
