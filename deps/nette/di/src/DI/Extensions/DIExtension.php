<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Packetery\Nette\DI\Extensions;

use Packetery\Nette;
use Packetery\Tracy;
/**
 * DI extension.
 */
final class DIExtension extends \Packetery\Nette\DI\CompilerExtension
{
    /** @var array */
    public $exportedTags = [];
    /** @var array */
    public $exportedTypes = [];
    /** @var bool */
    private $debugMode;
    /** @var float */
    private $time;
    public function __construct(bool $debugMode = \false)
    {
        $this->debugMode = $debugMode;
        $this->time = \microtime(\true);
        $this->config = new class
        {
            /** @var ?bool */
            public $debugger;
            /** @var string[] */
            public $excluded = [];
            /** @var ?string */
            public $parentClass;
            /** @var object */
            public $export;
        };
        $this->config->export = new class
        {
            /** @var bool */
            public $parameters = \true;
            /** @var string[]|bool|null */
            public $tags = \true;
            /** @var string[]|bool|null */
            public $types = \true;
        };
    }
    public function loadConfiguration()
    {
        $builder = $this->getContainerBuilder();
        $builder->addExcludedClasses($this->config->excluded);
    }
    public function afterCompile(\Packetery\Nette\PhpGenerator\ClassType $class)
    {
        if ($this->config->parentClass) {
            $class->setExtends($this->config->parentClass);
        }
        $this->restrictParameters($class);
        $this->restrictTags($class);
        $this->restrictTypes($class);
        if ($this->debugMode && ($this->config->debugger ?? $this->getContainerBuilder()->getByType(Tracy\Bar::class))) {
            $this->enableTracyIntegration();
        }
    }
    private function restrictParameters(\Packetery\Nette\PhpGenerator\ClassType $class) : void
    {
        if (!$this->config->export->parameters) {
            $class->removeMethod('getParameters');
            $class->removeMethod('getStaticParameters');
        }
    }
    private function restrictTags(\Packetery\Nette\PhpGenerator\ClassType $class) : void
    {
        $option = $this->config->export->tags;
        if ($option === \true) {
        } elseif ($option === \false) {
            $class->removeProperty('tags');
        } elseif ($prop = $class->getProperties()['tags'] ?? null) {
            $prop->setValue(\array_intersect_key($prop->getValue(), $this->exportedTags + \array_flip((array) $option)));
        }
    }
    private function restrictTypes(\Packetery\Nette\PhpGenerator\ClassType $class) : void
    {
        $option = $this->config->export->types;
        if ($option === \true) {
            return;
        }
        $prop = $class->getProperty('wiring');
        $prop->setValue(\array_intersect_key($prop->getValue(), $this->exportedTypes + (\is_array($option) ? \array_flip($option) : [])));
    }
    private function enableTracyIntegration() : void
    {
        \Packetery\Nette\Bridges\DITracy\ContainerPanel::$compilationTime = $this->time;
        $this->initialization->addBody($this->getContainerBuilder()->formatPhp('?;', [new \Packetery\Nette\DI\Definitions\Statement('@Tracy\\Bar::addPanel', [new \Packetery\Nette\DI\Definitions\Statement(\Packetery\Nette\Bridges\DITracy\ContainerPanel::class)])]));
    }
}
