<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Packetery\Symfony\Component\String;

if (!\function_exists(u::class)) {
    /** @internal */
    function u(?string $string = '') : UnicodeString
    {
        return new UnicodeString($string ?? '');
    }
}
if (!\function_exists(b::class)) {
    /** @internal */
    function b(?string $string = '') : ByteString
    {
        return new ByteString($string ?? '');
    }
}
if (!\function_exists(s::class)) {
    /**
     * @return UnicodeString|ByteString
     * @internal
     */
    function s(?string $string = '') : AbstractString
    {
        $string = $string ?? '';
        return \preg_match('//u', $string) ? new UnicodeString($string) : new ByteString($string);
    }
}
