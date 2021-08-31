<?php

/**
 * This file is part of the PacketeryNette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace PacketeryNette\Bridges\FormsDI;

use PacketeryNette;


/**
 * Forms extension for PacketeryNette DI.
 */
class FormsExtension extends PacketeryNette\DI\CompilerExtension
{
	public function __construct()
	{
		$this->config = new class {
			/** @var string[] */
			public $messages = [];
		};
	}


	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();

		if ($builder->findByType(PacketeryNette\Http\IRequest::class)) {
			$builder->addDefinition($this->prefix('factory'))
				->setFactory(PacketeryNette\Forms\FormFactory::class);
		}
	}


	public function afterCompile(PacketeryNette\PhpGenerator\ClassType $class)
	{
		$initialize = $this->initialization ?? $class->getMethod('initialize');

		foreach ($this->config->messages as $name => $text) {
			if (defined('PacketeryNette\Forms\Form::' . $name)) {
				$initialize->addBody('PacketeryNette\Forms\Validator::$messages[PacketeryNette\Forms\Form::?] = ?;', [$name, $text]);
			} elseif (defined($name)) {
				$initialize->addBody('PacketeryNette\Forms\Validator::$messages[' . $name . '] = ?;', [$text]);
			} else {
				throw new PacketeryNette\InvalidArgumentException('Constant PacketeryNette\Forms\Form::' . $name . ' or constant ' . $name . ' does not exist.');
			}
		}
	}
}
