<?php

/**
 * This file is part of the PacketeryTracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace PacketeryTracy\Bridges\PacketeryNette;

use PacketeryNette;
use PacketeryNette\Schema\Expect;
use PacketeryTracy;


/**
 * PacketeryTracy extension for PacketeryNette DI.
 */
class PacketeryTracyExtension extends PacketeryNette\DI\CompilerExtension
{
	/** @var bool */
	private $debugMode;

	/** @var bool */
	private $cliMode;


	public function __construct(bool $debugMode = false, bool $cliMode = false)
	{
		$this->debugMode = $debugMode;
		$this->cliMode = $cliMode;
	}


	public function getConfigSchema(): PacketeryNette\Schema\Schema
	{
		return Expect::structure([
			'email' => Expect::anyOf(Expect::email(), Expect::listOf('email'))->dynamic(),
			'fromEmail' => Expect::email()->dynamic(),
			'logSeverity' => Expect::anyOf(Expect::scalar(), Expect::listOf('scalar')),
			'editor' => Expect::string()->dynamic(),
			'browser' => Expect::string()->dynamic(),
			'errorTemplate' => Expect::string()->dynamic(),
			'strictMode' => Expect::bool()->dynamic(),
			'showBar' => Expect::bool()->dynamic(),
			'maxLength' => Expect::int()->dynamic(),
			'maxDepth' => Expect::int()->dynamic(),
			'keysToHide' => Expect::array(null)->dynamic(),
			'dumpTheme' => Expect::string()->dynamic(),
			'showLocation' => Expect::bool()->dynamic(),
			'scream' => Expect::bool()->dynamic(),
			'bar' => Expect::listOf('string|PacketeryNette\DI\Definitions\Statement'),
			'blueScreen' => Expect::listOf('callable'),
			'editorMapping' => Expect::arrayOf('string')->dynamic()->default(null),
			'netteMailer' => Expect::bool(true),
		]);
	}


	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('logger'))
			->setClass(PacketeryTracy\ILogger::class)
			->setFactory([PacketeryTracy\Debugger::class, 'getLogger']);

		$builder->addDefinition($this->prefix('blueScreen'))
			->setFactory([PacketeryTracy\Debugger::class, 'getBlueScreen']);

		$builder->addDefinition($this->prefix('bar'))
			->setFactory([PacketeryTracy\Debugger::class, 'getBar']);
	}


	public function afterCompile(PacketeryNette\PhpGenerator\ClassType $class)
	{
		$initialize = $this->initialization ?? new PacketeryNette\PhpGenerator\Closure;
		$initialize->addBody('if (!PacketeryTracy\Debugger::isEnabled()) { return; }');

		$builder = $this->getContainerBuilder();

		$options = (array) $this->config;
		unset($options['bar'], $options['blueScreen'], $options['netteMailer']);
		if (isset($options['logSeverity'])) {
			$res = 0;
			foreach ((array) $options['logSeverity'] as $level) {
				$res |= is_int($level) ? $level : constant($level);
			}
			$options['logSeverity'] = $res;
		}
		foreach ($options as $key => $value) {
			if ($value !== null) {
				static $tbl = [
					'keysToHide' => 'array_push(PacketeryTracy\Debugger::getBlueScreen()->keysToHide, ... ?)',
					'fromEmail' => 'PacketeryTracy\Debugger::getLogger()->fromEmail = ?',
				];
				$initialize->addBody($builder->formatPhp(
					($tbl[$key] ?? 'PacketeryTracy\Debugger::$' . $key . ' = ?') . ';',
					PacketeryNette\DI\Helpers::filterArguments([$value])
				));
			}
		}

		$logger = $builder->getDefinition($this->prefix('logger'));
		if (
			!$logger instanceof PacketeryNette\DI\ServiceDefinition
			|| $logger->getFactory()->getEntity() !== [PacketeryTracy\Debugger::class, 'getLogger']
		) {
			$initialize->addBody($builder->formatPhp('PacketeryTracy\Debugger::setLogger(?);', [$logger]));
		}
		if ($this->config->netteMailer && $builder->getByType(PacketeryNette\Mail\IMailer::class)) {
			$initialize->addBody($builder->formatPhp('PacketeryTracy\Debugger::getLogger()->mailer = ?;', [
				[new PacketeryNette\DI\Statement(PacketeryTracy\Bridges\PacketeryNette\MailSender::class, ['fromEmail' => $this->config->fromEmail]), 'send'],
			]));
		}

		if ($this->debugMode) {
			foreach ($this->config->bar as $item) {
				if (is_string($item) && substr($item, 0, 1) === '@') {
					$item = new PacketeryNette\DI\Statement(['@' . $builder::THIS_CONTAINER, 'getService'], [substr($item, 1)]);
				} elseif (is_string($item)) {
					$item = new PacketeryNette\DI\Statement($item);
				}
				$initialize->addBody($builder->formatPhp(
					'$this->getService(?)->addPanel(?);',
					PacketeryNette\DI\Helpers::filterArguments([$this->prefix('bar'), $item])
				));
			}

			if (!$this->cliMode && ($name = $builder->getByType(PacketeryNette\Http\Session::class))) {
				$initialize->addBody('$this->getService(?)->start();', [$name]);
				$initialize->addBody('PacketeryTracy\Debugger::dispatch();');
			}
		}

		foreach ($this->config->blueScreen as $item) {
			$initialize->addBody($builder->formatPhp(
				'$this->getService(?)->addPanel(?);',
				PacketeryNette\DI\Helpers::filterArguments([$this->prefix('blueScreen'), $item])
			));
		}

		if (empty($this->initialization)) {
			$class->getMethod('initialize')->addBody("($initialize)();");
		}

		if (($dir = PacketeryTracy\Debugger::$logDirectory) && !is_writable($dir)) {
			throw new PacketeryNette\InvalidStateException("Make directory '$dir' writable.");
		}
	}
}
