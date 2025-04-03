<?php
declare(strict_types=1);

namespace Packetery\Module;

use Packetery\Nette\Http\Request;
use Packetery\Nette\Http\RequestFactory;
use Packetery\Nette\Http\UrlScript;

final class HttpRequestFactory
{
	/**
	 * @var RequestFactory
	 */
	private $originalHttpRequestFactory;

	/**
	 * @var bool
	 */
	private $consoleMode;

	/**
	 * @var bool
	 */
	private $binary;

	public function __construct(
		bool $consoleMode,
		bool $binary,
		RequestFactory $originalHttpRequestFactory
	)
	{
		$this->originalHttpRequestFactory = $originalHttpRequestFactory;
		$this->consoleMode = $consoleMode;
		$this->binary = $binary;
	}


	public function createHttpRequest(): Request
	{
		if ($this->consoleMode) {
			$urlScript = new UrlScript('/', '/');
			return new Request($urlScript);
		}

		$this->originalHttpRequestFactory->setBinary($this->binary);
		return $this->originalHttpRequestFactory->fromGlobals();
	}

}
