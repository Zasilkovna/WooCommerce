<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Packetery\Nette\Forms\Controls;

use Packetery\Nette;
/**
 * Multiline text input control.
 * @internal
 */
class TextArea extends TextBase
{
    /**
     * @param  string|object  $label
     */
    public function __construct($label = null)
    {
        parent::__construct($label);
        $this->control->setName('textarea');
        $this->setOption('type', 'textarea');
    }
    public function getControl() : \Packetery\Nette\Utils\Html
    {
        return parent::getControl()->setText((string) $this->getRenderedValue());
    }
}
