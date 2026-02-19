<?php

namespace Packetery\Module\Order;

use Packetery\Core\Entity\Order;
use Packetery\Module\Options\OptionsProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CurrencyConversionTest extends TestCase {

	private OptionsProvider&MockObject $optionsProvider;
	private CurrencyConversion $currencyConversion;

	protected function setUp(): void {
		$this->optionsProvider    = $this->createMock( OptionsProvider::class );
		$this->currencyConversion = new CurrencyConversion( $this->optionsProvider );
	}

	public function testGetList(): void {
		$list = $this->currencyConversion->getList();
		$this->assertContains( 'CZK', $list );
		$this->assertContains( 'EUR', $list );
		$this->assertContains( 'HUF', $list );
	}

	public static function countryCurrencyProvider(): array {
		return [
			'Czech Republic'  => [ 'CZ', 'CZK', 0.04, 'EUR', [ 0.04, 'CZK' ] ],
			'Germany'         => [ 'DE', 'EUR', 1.00, 'EUR', [ 1.00, 'EUR' ] ],
			'Hungary'         => [ 'HU', 'HUF', 0.24, 'EUR', [ 0.24, 'HUF' ] ],
			'Poland'          => [ 'PL', 'PLN', 0.20, 'EUR', [ 0.20, 'PLN' ] ],
			'unknown country' => [ 'XX', '', null, 'EUR', [ 1.00, 'EUR' ] ],
			'same currency'   => [ 'SK', 'EUR', 1.00, 'EUR', [ 1.00, 'EUR' ] ],
			'null country'    => [ null, '', null, 'EUR', [ 1.00, 'EUR' ] ],
			'null rate'       => [ 'CZ', 'CZK', null, 'EUR', [ 1.00, 'EUR' ] ],
		];
	}

	/**
	 * @dataProvider countryCurrencyProvider
	 */
	public function testGetOrderCustomCurrencyRate(
		?string $country,
		string $expectedCurrency,
		?float $rate,
		string $orderCurrency,
		array $expectedResult
	): void {
		$order = $this->createMock( Order::class );
		$order->method( 'getShippingCountry' )->willReturn( $country );
		$order->method( 'getCurrency' )->willReturn( $orderCurrency );

		$this->optionsProvider->method( 'isCustomCurrencyRatesEnabled' )->willReturn( true );
		$this->optionsProvider->method( 'getCustomCurrencyRate' )->with( $expectedCurrency )->willReturn( $rate );

		$result = $this->currencyConversion->getOrderCustomCurrencyRate( $order );
		$this->assertEquals( $expectedResult, $result );
	}

	public function testGetOrderCustomCurrencyRateDisabled(): void {
		$order = $this->createMock( Order::class );
		$this->optionsProvider->method( 'isCustomCurrencyRatesEnabled' )->willReturn( false );
		$order->method( 'getCurrency' )->willReturn( 'EUR' );

		$result = $this->currencyConversion->getOrderCustomCurrencyRate( $order );
		$this->assertEquals( [ 1.0, 'EUR' ], $result );
	}
}
