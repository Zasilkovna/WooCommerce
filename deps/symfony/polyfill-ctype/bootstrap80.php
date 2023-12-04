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
use Packetery\Symfony\Polyfill\Ctype as p;
if (!\function_exists('ctype_alnum')) {
    /** @internal */
    function ctype_alnum(mixed $text) : bool
    {
        return p\Ctype::ctype_alnum($text);
    }
}
if (!\function_exists('ctype_alpha')) {
    /** @internal */
    function ctype_alpha(mixed $text) : bool
    {
        return p\Ctype::ctype_alpha($text);
    }
}
if (!\function_exists('ctype_cntrl')) {
    /** @internal */
    function ctype_cntrl(mixed $text) : bool
    {
        return p\Ctype::ctype_cntrl($text);
    }
}
if (!\function_exists('ctype_digit')) {
    /** @internal */
    function ctype_digit(mixed $text) : bool
    {
        return p\Ctype::ctype_digit($text);
    }
}
if (!\function_exists('ctype_graph')) {
    /** @internal */
    function ctype_graph(mixed $text) : bool
    {
        return p\Ctype::ctype_graph($text);
    }
}
if (!\function_exists('ctype_lower')) {
    /** @internal */
    function ctype_lower(mixed $text) : bool
    {
        return p\Ctype::ctype_lower($text);
    }
}
if (!\function_exists('ctype_print')) {
    /** @internal */
    function ctype_print(mixed $text) : bool
    {
        return p\Ctype::ctype_print($text);
    }
}
if (!\function_exists('ctype_punct')) {
    /** @internal */
    function ctype_punct(mixed $text) : bool
    {
        return p\Ctype::ctype_punct($text);
    }
}
if (!\function_exists('ctype_space')) {
    /** @internal */
    function ctype_space(mixed $text) : bool
    {
        return p\Ctype::ctype_space($text);
    }
}
if (!\function_exists('ctype_upper')) {
    /** @internal */
    function ctype_upper(mixed $text) : bool
    {
        return p\Ctype::ctype_upper($text);
    }
}
if (!\function_exists('ctype_xdigit')) {
    /** @internal */
    function ctype_xdigit(mixed $text) : bool
    {
        return p\Ctype::ctype_xdigit($text);
    }
}
