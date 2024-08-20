<?php

declare( strict_types=1 );

namespace Tests\Module;

use Packetery\Module\CurrencySwitcherFacade;
use Packetery\Module\Framework\WpAdapter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MockFactory {
	private TestCase $testCase;

	public function __construct( TestCase $testCase ) {
		$this->testCase = $testCase;
	}

	public function createWpAdapter(): WpAdapter|MockObject {
		$mock = $this->testCase->getMockBuilder( WpAdapter::class )->getMock();
		$mock
			->method( 'applyFilters' )
			->willReturnCallback( static function ( string $hookName, $value ) {
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
