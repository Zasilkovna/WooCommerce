<?php

/**
 * This file is part of the PacketeryLatte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace PacketeryLatte\Bridges\Tracy;

use PacketeryLatte\Engine;
use PacketeryLatte\Runtime\Template;
use Nette;
use Tracy;


/**
 * Bar panel for Tracy 2.x
 */
class PacketeryLattePanel implements Tracy\IBarPanel
{
	use Nette\SmartObject;

	/** @var bool */
	public $dumpParameters = true;

	/** @var Template[] */
	private $templates = [];

	/** @var array */
	private $list;

	/** @var string|null */
	private $name;


	public static function initialize(Engine $latte, string $name = null, Tracy\Bar $bar = null): void
	{
		$bar = $bar ?? Tracy\Debugger::getBar();
		$bar->addPanel(new self($latte, $name));
	}


	public function __construct(Engine $latte, string $name = null)
	{
		$this->name = $name;
		$latte->probe = function (Template $template): void {
			$this->templates[] = $template;
		};
	}


	/**
	 * Renders tab.
	 */
	public function getTab(): ?string
	{
		if (!$this->templates) {
			return null;
		}

		return Nette\Utils\Helpers::capture(function () {
			$name = $this->name ?? basename(reset($this->templates)->getName());
			require __DIR__ . '/templates/PacketeryLattePanel.tab.phtml';
		});
	}


	/**
	 * Renders panel.
	 */
	public function getPanel(): string
	{
		$this->list = [];
		$this->buildList($this->templates[0]);

		return Nette\Utils\Helpers::capture(function () {
			$list = $this->list;
			$dumpParameters = $this->dumpParameters;
			require __DIR__ . '/templates/PacketeryLattePanel.panel.phtml';
		});
	}


	private function buildList(Template $template, int $depth = 0, int $count = 1)
	{
		$this->list[] = (object) [
			'template' => $template,
			'depth' => $depth,
			'count' => $count,
			'phpFile' => (new \ReflectionObject($template))->getFileName(),
		];

		$children = $counter = [];
		foreach ($this->templates as $t) {
			if ($t->getReferringTemplate() === $template) {
				$children[$t->getName()] = $t;
				@$counter[$t->getName()]++;
			}
		}

		foreach ($children as $name => $t) {
			$this->buildList($t, $depth + 1, $counter[$name]);
		}
	}
}
