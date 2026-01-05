<?php

declare( strict_types=1 );

namespace Tests\Module\Log;

use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Log\ArgumentTypeErrorLogger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use WC_Logger_Interface;

class ArgumentTypeErrorLoggerTest extends TestCase {

	private WcAdapter&MockObject $wcAdapterMock;
	private WpAdapter&MockObject $wpAdapterMock;
	private WC_Logger_Interface&MockObject $wcLoggerMock;

	private function createArgumentTypeErrorLogger(): ArgumentTypeErrorLogger {
		$this->wcAdapterMock = $this->createMock( WcAdapter::class );
		$this->wpAdapterMock = $this->createMock( WpAdapter::class );
		$this->wcLoggerMock  = $this->createMock( WC_Logger_Interface::class );

		$this->wcAdapterMock
			->method( 'getLogger' )
			->willReturn( $this->wcLoggerMock );

		return new ArgumentTypeErrorLogger( $this->wcAdapterMock, $this->wpAdapterMock );
	}

	public function testLogCallsWcLoggerWithCorrectParameters(): void {
		$logger = $this->createArgumentTypeErrorLogger();

		$this->wpAdapterMock
			->method( 'didAction' )
			->with( 'woocommerce_init' )
			->willReturn( 1 );

		$this->wcLoggerMock
			->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->equalTo( 'error' ),
				$this->stringContains( 'Method testMethod expects parameter "testParam" to be type of "string", type "integer" given' ),
				$this->equalTo( [ 'source' => 'packeta' ] )
			);

		$logger->log( 'testMethod', 'testParam', 'string', 123 );
	}

	public function testLogIncludesStackTrace(): void {
		$logger = $this->createArgumentTypeErrorLogger();

		$this->wpAdapterMock
			->method( 'didAction' )
			->with( 'woocommerce_init' )
			->willReturn( 1 );

		$this->wcLoggerMock
			->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->anything(),
				$this->stringContains( '#0' ),
				$this->anything()
			);

		$logger->log( 'testMethod', 'testParam', 'string', 123 );
	}

	public function testLogHandlesObjectType(): void {
		$logger = $this->createArgumentTypeErrorLogger();

		$this->wpAdapterMock
			->method( 'didAction' )
			->with( 'woocommerce_init' )
			->willReturn( 1 );

		$this->wcLoggerMock
			->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->anything(),
				$this->stringContains( 'string' ),
				$this->anything()
			);

		$logger->log( 'testMethod', 'testParam', 'object', 'string' );
	}

	public function testLogHandlesArrayType(): void {
		$logger = $this->createArgumentTypeErrorLogger();

		$this->wpAdapterMock
			->method( 'didAction' )
			->with( 'woocommerce_init' )
			->willReturn( 1 );

		$this->wcLoggerMock
			->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->anything(),
				$this->stringContains( 'string' ),
				$this->anything()
			);

		$logger->log( 'testMethod', 'testParam', 'array', 'string' );
	}

	public function testLogHandlesNullValue(): void {
		$logger = $this->createArgumentTypeErrorLogger();

		$this->wpAdapterMock
			->method( 'didAction' )
			->with( 'woocommerce_init' )
			->willReturn( 1 );

		$this->wcLoggerMock
			->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->anything(),
				$this->stringContains( 'NULL' ),
				$this->anything()
			);

		$logger->log( 'testMethod', 'testParam', 'string', null );
	}

	public function testLogHandlesBooleanValue(): void {
		$logger = $this->createArgumentTypeErrorLogger();

		$this->wpAdapterMock
			->method( 'didAction' )
			->with( 'woocommerce_init' )
			->willReturn( 1 );

		$this->wcLoggerMock
			->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->anything(),
				$this->stringContains( 'boolean' ),
				$this->anything()
			);

		$logger->log( 'testMethod', 'testParam', 'bool', 'boolean' );
	}

	public function testLogHandlesFloatValue(): void {
		$logger = $this->createArgumentTypeErrorLogger();

		$this->wpAdapterMock
			->method( 'didAction' )
			->with( 'woocommerce_init' )
			->willReturn( 1 );

		$this->wcLoggerMock
			->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->anything(),
				$this->stringContains( 'string' ),
				$this->anything()
			);

		$logger->log( 'testMethod', 'testParam', 'float', 'string' );
	}

	public function testLogCallsWriteWhenWooCommerceInitAlreadyFired(): void {
		$logger = $this->createArgumentTypeErrorLogger();

		$this->wpAdapterMock
			->expects( $this->once() )
			->method( 'didAction' )
			->with( 'woocommerce_init' )
			->willReturn( 1 );

		$this->wcLoggerMock
			->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->equalTo( 'error' ),
				$this->stringContains( 'Method testMethod' ),
				$this->equalTo( [ 'source' => 'packeta' ] )
			);

		$logger->log( 'testMethod', 'testParam', 'string', 123 );
	}

	public function testLogRegistersActionHookWhenWooCommerceInitNotFired(): void {
		$logger = $this->createArgumentTypeErrorLogger();

		$this->wpAdapterMock
			->expects( $this->once() )
			->method( 'didAction' )
			->with( 'woocommerce_init' )
			->willReturn( 0 );

		$this->wpAdapterMock
			->expects( $this->once() )
			->method( 'addAction' )
			->with(
				'woocommerce_init',
				$this->isInstanceOf( \Closure::class )
			);

		$this->wcLoggerMock
			->expects( $this->never() )
			->method( 'log' );

		$logger->log( 'testMethod', 'testParam', 'string', 123 );
	}

	public function testLogActionHookCallsWriteWhenWooCommerceInitFires(): void {
		$logger = $this->createArgumentTypeErrorLogger();

		$callback = null;
		$this->wpAdapterMock
			->expects( $this->once() )
			->method( 'didAction' )
			->with( 'woocommerce_init' )
			->willReturn( 0 );

		$this->wpAdapterMock
			->expects( $this->once() )
			->method( 'addAction' )
			->with(
				'woocommerce_init',
				$this->callback(
					function ( $arg ) use ( &$callback ): bool {
						$callback = $arg;

						return $arg instanceof \Closure;
					}
				)
			);

		$logger->log( 'testMethod', 'testParam', 'string', 123 );

		$this->assertNotNull( $callback );

		$this->wcLoggerMock
			->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->equalTo( 'error' ),
				$this->stringContains( 'Method testMethod' ),
				$this->equalTo( [ 'source' => 'packeta' ] )
			);

		$callback();
	}
}
