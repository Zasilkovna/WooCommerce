<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Packetery\Nette\Forms\Controls;

use Packetery\Nette\Utils\Html;
/**
 * Push button control with no default behavior.
 * @internal
 */
class Button extends BaseControl
{
    /**
     * @param  string|object  $caption
     */
    public function __construct($caption = null)
    {
        parent::__construct($caption);
        $this->control->type = 'button';
        $this->setOption('type', 'button');
    }
    /**
     * Is button pressed?
     */
    public function isFilled() : bool
    {
        $value = $this->getValue();
        return $value !== null && $value !== [];
    }
    /**
     * Bypasses label generation.
     */
    public function getLabel($caption = null)
    {
        return null;
    }
    /** @return static */
    public function renderAsButton(bool $state = \true)
    {
        $this->control->setName($state ? 'button' : 'input');
        return $this;
    }
    /**
     * Generates control's HTML element.
     * @param  string|object  $caption
     */
    public function getControl($caption = null) : Html
    {
        $this->setOption('rendered', \true);
        $caption = $this->translate($caption ?? $this->getCaption());
        $el = (clone $this->control)->addAttributes(['name' => $this->getHtmlName(), 'disabled' => $this->isDisabled()]);
        if ($caption instanceof Html || $el->getName() === 'button') {
            $el->setName('button')->setText($caption);
        } else {
            $el->value = $caption;
        }
        return $el;
    }
}
