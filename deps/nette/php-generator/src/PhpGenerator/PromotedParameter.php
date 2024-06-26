<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Packetery\Nette\PhpGenerator;

/**
 * Promoted parameter in constructor.
 * @internal
 */
final class PromotedParameter extends Parameter
{
    use Traits\VisibilityAware;
    use Traits\CommentAware;
}
