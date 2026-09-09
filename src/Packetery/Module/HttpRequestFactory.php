<?php
declare(strict_types=1);

namespace Packetery\Module;

use Packetery\Nette\Http\Request;
use Packetery\Nette\Http\RequestFactory;
use Packetery\Nette\Http\UrlScript;
use Packetery\Nette\InvalidArgumentException;

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

		try {
			return $this->originalHttpRequestFactory->fromGlobals();
		} catch ( InvalidArgumentException $e ) {
			// Nette refuses a REQUEST_URI whose path has no slash ('', '?foo=1', 'wp-cron.php'), which is what some cron runners pass.
			$urlScript = new UrlScript( '/', '/' );

			return new Request( $urlScript );
		}
	}
}
