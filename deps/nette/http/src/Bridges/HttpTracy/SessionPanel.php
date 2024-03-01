<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Packetery\Nette\Bridges\HttpTracy;

use Packetery\Nette;
use Packetery\Tracy;
/**
 * Session panel for Debugger Bar.
 * @internal
 */
class SessionPanel implements Tracy\IBarPanel
{
    use \Packetery\Nette\SmartObject;
    /**
     * Renders tab.
     */
    public function getTab() : string
    {
        return \Packetery\Nette\Utils\Helpers::capture(function () {
            require __DIR__ . '/templates/SessionPanel.tab.phtml';
        });
    }
    /**
     * Renders panel.
     */
    public function getPanel() : string
    {
        return \Packetery\Nette\Utils\Helpers::capture(function () {
            require __DIR__ . '/templates/SessionPanel.panel.phtml';
        });
    }
}
