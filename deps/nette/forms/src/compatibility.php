<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Packetery\Nette\Forms;

if (\false) {
    /** @deprecated use \Packetery\Nette\Forms\Control
     * @internal
     */
    class IControl extends Control
    {
    }
} elseif (!\interface_exists(IControl::class)) {
    \class_alias(Control::class, IControl::class);
}
if (\false) {
    /** @deprecated use \Packetery\Nette\Forms\FormRenderer
     * @internal
     */
    class IFormRenderer extends FormRenderer
    {
    }
} elseif (!\interface_exists(IFormRenderer::class)) {
    \class_alias(FormRenderer::class, IFormRenderer::class);
}
if (\false) {
    /** @deprecated use \Packetery\Nette\Forms\SubmitterControl
     * @internal
     */
    class ISubmitterControl extends SubmitterControl
    {
    }
} elseif (!\interface_exists(ISubmitterControl::class)) {
    \class_alias(SubmitterControl::class, ISubmitterControl::class);
}
