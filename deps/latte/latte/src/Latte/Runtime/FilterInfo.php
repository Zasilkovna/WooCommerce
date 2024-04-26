<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Packetery\Latte\Runtime;

use Packetery\Latte;
/**
 * Filter runtime info
 * @internal
 */
class FilterInfo
{
    use \Packetery\Latte\Strict;
    /** @var string|null */
    public $contentType;
    public function __construct(?string $contentType = null)
    {
        $this->contentType = $contentType;
    }
    public function validate(array $contentTypes, ?string $name = null) : void
    {
        if (!\in_array($this->contentType, $contentTypes, \true)) {
            $name = $name ? " |{$name}" : $name;
            $type = $this->contentType ? ' ' . \strtoupper($this->contentType) : '';
            throw new \Packetery\Latte\RuntimeException("Filter{$name} used with incompatible type{$type}.");
        }
    }
}
