<?php

declare( strict_types=1 );

namespace Tests\Module;

use Packetery\Module\Bridge;
use Packetery\Module\CurrencySwitcherFacade;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MockFactory {
	private TestCase $testCase;

	public function __construct( TestCase $testCase ) {
		$this->testCase = $testCase;
	}

	public function createBridge(): Bridge|MockObject {
		$mock = $this->testCase->getMockBuilder( Bridge::class )->getMock();
		$mock
			->method( 'applyFilters' )
			->willReturnCallback( static function ( $hookName, $value ) {
				return $value;
			} );

		return $mock;
	}

	public function createCurrencySwitcherFacade(): CurrencySwitcherFacade|MockObject {
		$mock = $this->testCase->getMockBuilder( CurrencySwitcherFacade::class )->getMock();
		$mock
			->method( 'getConvertedPrice' )
			->willReturnCallback( static function ( $value ) {
				return $value;
			} );

		return $mock;
	}
}