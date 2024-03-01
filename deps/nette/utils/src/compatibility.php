<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Packetery\Nette\Utils;

use Packetery\Nette;
if (\false) {
    /** @deprecated use \Packetery\Nette\HtmlStringable
     * @internal
     */
    interface IHtmlString extends \Packetery\Nette\HtmlStringable
    {
    }
} elseif (!\interface_exists(IHtmlString::class)) {
    \class_alias(\Packetery\Nette\HtmlStringable::class, IHtmlString::class);
}
namespace Packetery\Nette\Localization;

if (\false) {
    /** @deprecated use \Packetery\Nette\Localization\Translator
     * @internal
     */
    interface ITranslator extends Translator
    {
    }
} elseif (!\interface_exists(ITranslator::class)) {
    \class_alias(Translator::class, ITranslator::class);
}
