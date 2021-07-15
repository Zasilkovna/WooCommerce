<?php

declare(strict_types=1);

namespace Packetery;

class Translator implements \Nette\Localization\Translator
{
    function translate($message, ...$parameters): string {
        return translate($message, Plugin::DOMAIN);
    }
}
