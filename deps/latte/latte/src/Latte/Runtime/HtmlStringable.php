<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Packetery\Latte\Runtime;

/** @internal */
interface HtmlStringable
{
    /** @return string in HTML format */
    function __toString() : string;
}
\interface_exists(IHtmlString::class);
