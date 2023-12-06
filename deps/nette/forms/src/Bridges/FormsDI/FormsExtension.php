<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Packetery\Nette\Bridges\FormsDI;

use Packetery\Nette;
/**
 * Forms extension for Nette DI.
 * @internal
 */
class FormsExtension extends \Packetery\Nette\DI\CompilerExtension
{
    public function __construct()
    {
        $this->config = new class
        {
            /** @var string[] */
            public $messages = [];
        };
    }
    public function beforeCompile()
    {
        $builder = $this->getContainerBuilder();
        if ($builder->findByType(\Packetery\Nette\Http\IRequest::class)) {
            $builder->addDefinition($this->prefix('factory'))->setFactory(\Packetery\Nette\Forms\FormFactory::class);
        }
    }
    public function afterCompile(\Packetery\Nette\PhpGenerator\ClassType $class)
    {
        $initialize = $this->initialization ?? $class->getMethod('initialize');
        foreach ($this->config->messages as $name => $text) {
            if (\defined('\\Packetery\\Nette\\Forms\\Form::' . $name)) {
                $initialize->addBody('\\Packetery\\Nette\\Forms\\Validator::$messages[\\Packetery\\Nette\\Forms\\Form::?] = ?;', [$name, $text]);
            } elseif (\defined($name)) {
                $initialize->addBody('\\Packetery\\Nette\\Forms\\Validator::$messages[' . $name . '] = ?;', [$text]);
            } else {
                throw new \Packetery\Nette\InvalidArgumentException('Constant \\Packetery\\Nette\\Forms\\Form::' . $name . ' or constant ' . $name . ' does not exist.');
            }
        }
    }
}
