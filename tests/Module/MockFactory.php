<?php

declare( strict_types=1 );

namespace Tests\Module;

use Packetery\Module\CurrencySwitcherFacade;
use Packetery\Module\Framework\WpAdapter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MockFactory {

	public static function createWpAdapter( TestCase $testCase ): WpAdapter|MockObject {
		$mock = $testCase->getMockBuilder( WpAdapter::class )->getMock();
		$mock
			->method( 'applyFilters' )
			->willReturnCallback( static function ( string $hookName, $value ) {
				return $value;
			} );

		return $mock;
	}

	public static function createCurrencySwitcherFacade( TestCase $testCase ): CurrencySwitcherFacade|MockObject {
		$mock = $testCase->getMockBuilder( CurrencySwitcherFacade::class )->getMock();
		$mock
			->method( 'getConvertedPrice' )
			->willReturnCallback( static function ( $value ) {
				return $value;
			} );

		return $mock;
	}

}
