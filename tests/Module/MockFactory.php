<?php

declare( strict_types=1 );

namespace Tests\Module;

use Packetery\Module\Checkout\CurrencySwitcherService;
use Packetery\Module\Framework\WpAdapter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MockFactory {
	public static function createWpAdapter( TestCase $testCase ): WpAdapter|MockObject {
		$mock = $testCase->getMockBuilder( WpAdapter::class )->getMock();
		$mock
			->method( 'applyFilters' )
			->willReturnCallback(
				static function ( string $hookName, $value ) {
					return $value;
				}
			);

		return $mock;
	}

	public static function createCurrencySwitcherFacade( TestCase $testCase ): CurrencySwitcherService|MockObject {
		$mock = $testCase->getMockBuilder( CurrencySwitcherService::class )
			->disableOriginalConstructor()
			->getMock();
		$mock
			->method( 'getConvertedPrice' )
			->willReturnCallback(
				static function ( $value ) {
					return $value;
				}
			);

		return $mock;
	}
}
