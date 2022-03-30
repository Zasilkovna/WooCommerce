<?php

/**
 * This file is part of the PacketeryNette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace PacketeryNette\Bridges\HttpDI;

use PacketeryNette;
use PacketeryNette\Http\IResponse;
use PacketeryNette\Schema\Expect;


/**
 * Session extension for PacketeryNette DI.
 */
class SessionExtension extends PacketeryNette\DI\CompilerExtension
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
			'debugger' => Expect::bool(false),
			'autoStart' => Expect::anyOf('smart', true, false)->firstIsDefault(),
			'expiration' => Expect::string()->dynamic(),
			'handler' => Expect::string()->dynamic(),
			'readAndClose' => Expect::bool(),
			'cookieSamesite' => Expect::anyOf(IResponse::SAME_SITE_LAX, IResponse::SAME_SITE_STRICT, IResponse::SAME_SITE_NONE, true)
				->firstIsDefault(),
		])->otherItems('mixed');
	}


	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->config;

		$session = $builder->addDefinition($this->prefix('session'))
			->setFactory(PacketeryNette\Http\Session::class);

		if ($config->expiration) {
			$session->addSetup('setExpiration', [$config->expiration]);
		}
		if ($config->handler) {
			$session->addSetup('setHandler', [$config->handler]);
		}
		if (($config->cookieDomain ?? null) === 'domain') {
			$config->cookieDomain = $builder::literal('$this->getByType(PacketeryNette\Http\IRequest::class)->getUrl()->getDomain(2)');
		}
		if (isset($config->cookieSecure)) {
			trigger_error("The item 'session › cookieSecure' is deprecated, use 'http › cookieSecure' (it has default value 'auto').", E_USER_DEPRECATED);
			unset($config->cookieSecure);
		}
		if ($config->cookieSamesite === true) {
			trigger_error("In 'session › cookieSamesite' replace true with 'Lax'.", E_USER_DEPRECATED);
			$config->cookieSamesite = IResponse::SAME_SITE_LAX;
		}
		$this->compiler->addExportedType(PacketeryNette\Http\IRequest::class);

		if ($this->debugMode && $config->debugger) {
			$session->addSetup('@PacketeryTracy\Bar::addPanel', [
				new PacketeryNette\DI\Definitions\Statement(PacketeryNette\Bridges\HttpPacketeryTracy\SessionPanel::class),
			]);
		}

		$options = (array) $config;
		unset($options['expiration'], $options['handler'], $options['autoStart'], $options['debugger']);
		if ($options['readAndClose'] === null) {
			unset($options['readAndClose']);
		}
		if (!empty($options)) {
			$session->addSetup('setOptions', [$options]);
		}

		if ($this->name === 'session') {
			$builder->addAlias('session', $this->prefix('session'));
		}

		if (!$this->cliMode) {
			$name = $this->prefix('session');

			if ($config->autoStart === 'smart') {
				$this->initialization->addBody('$this->getService(?)->exists() && $this->getService(?)->start();', [$name, $name]);

			} elseif ($config->autoStart) {
				$this->initialization->addBody('$this->getService(?)->start();', [$name]);
			}
		}
	}
}
