<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Packetery\Nette\Forms\Controls;

use Packetery\Nette;
use Packetery\Nette\Utils\Html;
/**
 * Check box control. Allows the user to select a true or false condition.
 * @internal
 */
class Checkbox extends BaseControl
{
    /** @var Html  wrapper element template */
    private $container;
    /**
     * @param  string|object  $label
     */
    public function __construct($label = null)
    {
        parent::__construct($label);
        $this->control->type = 'checkbox';
        $this->container = Html::el();
        $this->setOption('type', 'checkbox');
    }
    /**
     * Sets control's value.
     * @return static
     * @internal
     */
    public function setValue($value)
    {
        if (!\is_scalar($value) && $value !== null) {
            throw new \Packetery\Nette\InvalidArgumentException(\sprintf("Value must be scalar or null, %s given in field '%s'.", \gettype($value), $this->name));
        }
        $this->value = (bool) $value;
        return $this;
    }
    public function isFilled() : bool
    {
        return $this->getValue() !== \false;
        // back compatibility
    }
    public function getControl() : Html
    {
        return $this->container->setHtml($this->getLabelPart()->insert(0, $this->getControlPart()));
    }
    /**
     * Bypasses label generation.
     */
    public function getLabel($caption = null)
    {
        return null;
    }
    public function getControlPart() : Html
    {
        return parent::getControl()->checked($this->value);
    }
    public function getLabelPart() : Html
    {
        return parent::getLabel();
    }
    /**
     * Returns container HTML element template.
     */
    public function getContainerPrototype() : Html
    {
        return $this->container;
    }
    /** @deprecated  use getContainerPrototype() */
    public function getSeparatorPrototype() : Html
    {
        return $this->container;
    }
}
