<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Packetery\Nette\Forms;

use Packetery\Nette;
/**
 * Creates form.
 * @internal
 */
final class FormFactory
{
    use \Packetery\Nette\StaticClass;
    /** @var \Packetery\Nette\Http\IRequest */
    private $httpRequest;
    public function __construct(\Packetery\Nette\Http\IRequest $httpRequest)
    {
        $this->httpRequest = $httpRequest;
    }
    public function createForm(string $name = null) : Form
    {
        $form = new Form($name);
        $form->setHttpRequest($this->httpRequest);
        return $form;
    }
}
