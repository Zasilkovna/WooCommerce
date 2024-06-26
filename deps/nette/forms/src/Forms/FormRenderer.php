<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Packetery\Nette\Forms;

/**
 * Defines method that must implement form renderer.
 * @internal
 */
interface FormRenderer
{
    /**
     * Provides complete form rendering.
     */
    function render(Form $form) : string;
}
\interface_exists(IFormRenderer::class);
