<?php
declare(strict_types=1);

namespace Packetery\Module;

use Packetery\Nette\Http\Request;
use Packetery\Nette\Http\RequestFactory;
use Packetery\Nette\Http\UrlScript;

final class HttpRequestFactory {
	/**
	 * @var bool
	 */
	private $consoleMode;

	/**
	 * @var bool
	 */
	private $binary;

	/**
	 * @var RequestFactory
	 */
	private $originalHttpRequestFactory;

	public function __construct(
		bool $consoleMode,
		bool $binary,
		RequestFactory $originalHttpRequestFactory
	) {
		$this->consoleMode                = $consoleMode;
		$this->binary                     = $binary;
		$this->originalHttpRequestFactory = $originalHttpRequestFactory;
	}

	public function createHttpRequest(): Request {
		if ( $this->consoleMode ) {
			$urlScript = new UrlScript( '/', '/' );

			return new Request( $urlScript );
		}

		$this->originalHttpRequestFactory->setBinary( $this->binary );

		return $this->originalHttpRequestFactory->fromGlobals();
	}
}
