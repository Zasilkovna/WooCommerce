<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Packetery\Nette\PhpGenerator;

use Packetery\Nette;
/**
 * Promoted parameter in constructor.
 * @internal
 */
final class PromotedParameter extends Parameter
{
    use Traits\VisibilityAware;
    use Traits\CommentAware;
    /** @var bool */
    private $readOnly = \false;
    /** @return static */
    public function setReadOnly(bool $state = \true) : self
    {
        $this->readOnly = $state;
        return $this;
    }
    public function isReadOnly() : bool
    {
        return $this->readOnly;
    }
    /** @throws \Packetery\Nette\InvalidStateException */
    public function validate() : void
    {
        if ($this->readOnly && !$this->getType()) {
            throw new \Packetery\Nette\InvalidStateException("Property \${$this->getName()}: Read-only properties are only supported on typed property.");
        }
    }
}
