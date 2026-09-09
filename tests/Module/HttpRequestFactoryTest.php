<?php

namespace Tests\Module;

use Packetery\Module\HttpRequestFactory;
use Packetery\Nette\Http\RequestFactory;
use PHPUnit\Framework\TestCase;

class HttpRequestFactoryTest extends TestCase {
	/**
	 * @var array<string, mixed>
	 */
	private $serverBackup;

	protected function setUp(): void {
		$this->serverBackup = $_SERVER;
	}

	protected function tearDown(): void {
		$_SERVER = $this->serverBackup;
	}

	/**
	 * @return array<string, array{0: string|null, 1: string}>
	 */
	public static function requestUriProvider(): array {
		return [
			'missing REQUEST_URI'        => [ null, '/' ],
			'empty REQUEST_URI'          => [ '', '/' ],
			'query only'                 => [ '?foo=1', '/' ],
			'question mark only'         => [ '?', '/' ],
			'path without leading slash' => [ 'wp-cron.php', '/' ],
			'absolute url'               => [ 'http://example.com', '/' ],
			'whitespace only'            => [ '  ', '/' ],
			'root'                       => [ '/', '/' ],
			'regular path'               => [ '/wp-cron.php', '/wp-cron.php' ],
			'path with query'            => [ '/wp-admin/edit.php?post_type=shop_order', '/wp-admin/edit.php' ],
		];
	}

	/**
	 * @dataProvider requestUriProvider
	 */
	public function testMalformedRequestUriDoesNotBreakRequestCreation( ?string $requestUri, string $expectedPath ): void {
		// Without a host the URL parser stops normalizing a slashless path, which is what makes the failure reachable.
		unset(
			$_SERVER['REQUEST_URI'],
			$_SERVER['SCRIPT_NAME'],
			$_SERVER['HTTP_HOST'],
			$_SERVER['SERVER_NAME'],
			$_SERVER['QUERY_STRING']
		);
		if ( $requestUri !== null ) {
			$_SERVER['REQUEST_URI'] = $requestUri;
		}

		$factory = new HttpRequestFactory( false, false, new RequestFactory() );

		$this->assertSame( $expectedPath, $factory->createHttpRequest()->getUrl()->getPath() );
	}

	public function testConsoleModeSkipsGlobals(): void {
		$_SERVER['REQUEST_URI'] = '';

		$factory = new HttpRequestFactory( true, false, new RequestFactory() );

		$this->assertSame( '/', $factory->createHttpRequest()->getUrl()->getPath() );
	}
}
