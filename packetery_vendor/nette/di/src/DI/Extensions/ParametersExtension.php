<?php

/**
 * This file is part of the PacketeryNette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace PacketeryNette\DI\Extensions;

use PacketeryNette;
use PacketeryNette\DI\DynamicParameter;


/**
 * Parameters.
 */
final class ParametersExtension extends PacketeryNette\DI\CompilerExtension
{
	/** @var string[] */
	public $dynamicParams = [];

	/** @var string[][] */
	public $dynamicValidators = [];

	/** @var array */
	private $compilerConfig;


	public function __construct(array &$compilerConfig)
	{
		$this->compilerConfig = &$compilerConfig;
	}


	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$params = $this->config;
		$resolver = new PacketeryNette\DI\Resolver($builder);
		$generator = new PacketeryNette\DI\PhpGenerator($builder);

		foreach ($this->dynamicParams as $key) {
			$params[$key] = array_key_exists($key, $params)
				? new DynamicParameter($generator->formatPhp('($this->parameters[?] \?\? ?)', $resolver->completeArguments(PacketeryNette\DI\Helpers::filterArguments([$key, $params[$key]]))))
				: new DynamicParameter(PacketeryNette\PhpGenerator\Helpers::format('$this->parameters[?]', $key));
		}

		$builder->parameters = PacketeryNette\DI\Helpers::expand($params, $params, true);

		// expand all except 'services'
		$slice = array_diff_key($this->compilerConfig, ['services' => 1]);
		$slice = PacketeryNette\DI\Helpers::expand($slice, $builder->parameters);
		$this->compilerConfig = $slice + $this->compilerConfig;
	}


	public function afterCompile(PacketeryNette\PhpGenerator\ClassType $class)
	{
		$parameters = $this->getContainerBuilder()->parameters;
		array_walk_recursive($parameters, function (&$val): void {
			if ($val instanceof PacketeryNette\DI\Definitions\Statement || $val instanceof DynamicParameter) {
				$val = null;
			}
		});

		$cnstr = $class->getMethod('__construct');
		$cnstr->addBody('$this->parameters += ?;', [$parameters]);
		foreach ($this->dynamicValidators as [$param, $expected]) {
			if ($param instanceof PacketeryNette\DI\Definitions\Statement) {
				continue;
			}
			$cnstr->addBody('PacketeryNette\Utils\Validators::assert(?, ?, ?);', [$param, $expected, 'dynamic parameter']);
		}
	}
}
