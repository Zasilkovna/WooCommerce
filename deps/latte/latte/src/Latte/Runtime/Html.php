<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Packetery\Latte\Runtime;

use Packetery\Latte;
/**
 * HTML literal.
 * @internal
 */
class Html implements HtmlStringable
{
    use \Packetery\Latte\Strict;
    /** @var string */
    private $value;
    public function __construct($value)
    {
        $this->value = (string) $value;
    }
    public function __toString() : string
    {
        return $this->value;
    }
}
