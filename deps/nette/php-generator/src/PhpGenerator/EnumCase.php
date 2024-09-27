<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Packetery\Nette\PhpGenerator;

use Packetery\Nette;
/**
 * Enum case.
 * @internal
 */
final class EnumCase
{
    use \Packetery\Nette\SmartObject;
    use Traits\NameAware;
    use Traits\CommentAware;
    use Traits\AttributeAware;
    /** @var string|int|null */
    private $value;
    /** @return static */
    public function setValue($val) : self
    {
        $this->value = $val;
        return $this;
    }
    public function getValue()
    {
        return $this->value;
    }
}
