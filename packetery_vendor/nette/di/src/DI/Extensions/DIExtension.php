<?php

/**
 * This file is part of the PacketeryNette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace PacketeryNette\DI\Extensions;

use PacketeryNette;


/**
 * DI extension.
 */
final class DIExtension extends PacketeryNette\DI\CompilerExtension
{
	/** @var array */
	public $exportedTags = [];

	/** @var array */
	public $exportedTypes = [];

	/** @var bool */
	private $debugMode;

	/** @var float */
	private $time;


	public function __construct(bool $debugMode = false)
	{
		$this->debugMode = $debugMode;
		$this->time = microtime(true);

		$this->config = new class {
			/** @var ?bool */
			public $debugger;

			/** @var string[] */
			public $excluded = [];

			/** @var ?string */
			public $parentClass;

			/** @var object */
			public $export;
		};
		$this->config->export = new class {
			/** @var bool */
			public $parameters = true;

			/** @var string[]|bool|null */
			public $tags = true;

			/** @var string[]|bool|null */
			public $types = true;
		};
	}


	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$builder->addExcludedClasses($this->config->excluded);
	}


	public function beforeCompile()
	{
		if (!$this->config->export->parameters) {
			$this->getContainerBuilder()->parameters = [];
		}
	}


	public function afterCompile(PacketeryNette\PhpGenerator\ClassType $class)
	{
		if ($this->config->parentClass) {
			$class->setExtends($this->config->parentClass);
		}

		$this->restrictTags($class);
		$this->restrictTypes($class);

		if (
			$this->debugMode &&
			($this->config->debugger ?? $this->getContainerBuilder()->getByType(\PacketeryTracy\Bar::class))
		) {
			$this->enablePacketeryTracyIntegration();
		}

		$this->initializeTaggedServices();
	}


	private function restrictTags(PacketeryNette\PhpGenerator\ClassType $class): void
	{
		$option = $this->config->export->tags;
		if ($option === true) {
		} elseif ($option === false) {
			$class->removeProperty('tags');
		} elseif ($prop = $class->getProperties()['tags'] ?? null) {
			$prop->value = array_intersect_key($prop->value, $this->exportedTags + array_flip((array) $option));
		}
	}


	private function restrictTypes(PacketeryNette\PhpGenerator\ClassType $class): void
	{
		$option = $this->config->export->types;
		if ($option === true) {
			return;
		}
		$prop = $class->getProperty('wiring');
		$prop->value = array_intersect_key(
			$prop->value,
			$this->exportedTypes + (is_array($option) ? array_flip($option) : [])
		);
	}


	private function initializeTaggedServices(): void
	{
		foreach (array_filter($this->getContainerBuilder()->findByTag('run')) as $name => $on) {
			trigger_error("Tag 'run' used in service '$name' definition is deprecated.", E_USER_DEPRECATED);
			$this->initialization->addBody('$this->getService(?);', [$name]);
		}
	}


	private function enablePacketeryTracyIntegration(): void
	{
		PacketeryNette\Bridges\DIPacketeryTracy\ContainerPanel::$compilationTime = $this->time;
		$this->initialization->addBody($this->getContainerBuilder()->formatPhp('?;', [
			new PacketeryNette\DI\Definitions\Statement('@PacketeryTracy\Bar::addPanel', [new PacketeryNette\DI\Definitions\Statement(PacketeryNette\Bridges\DIPacketeryTracy\ContainerPanel::class)]),
		]));
	}
}
