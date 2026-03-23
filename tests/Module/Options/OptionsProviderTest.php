<?php

declare( strict_types=1 );

namespace Tests\Module\Options;

use Packetery\Core\Entity\PacketStatus;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionNames;
use Packetery\Module\Options\OptionsProvider;
use PHPUnit\Framework\TestCase;

class OptionsProviderTest extends TestCase {
	private const DUMMY_NONSENSE_VALUE = 'nonsense';

	public static function dimensionsUnitProvider(): array {
		return [
			[
				'unit'             => 'cm',
				'expectedDecimals' => 1,
			],
			[
				'unit'             => 'mm',
				'expectedDecimals' => 0,
			],
			[
				'unit'             => 'in',
				'expectedDecimals' => 0,
			],
			[
				'unit'             => 'l',
				'expectedDecimals' => 0,
			],
		];
	}

	/**
	 * @dataProvider dimensionsUnitProvider
	 */
	public function testGetDimensionsNumberOfDecimals( string $unit, int $expectedDecimals ): void {
		$provider = $this->getMockBuilder( OptionsProvider::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getDimensionsUnit' ] )
			->getMock();

		$provider->method( 'getDimensionsUnit' )
			->willReturn( $unit );

		$result = $provider->getDimensionsNumberOfDecimals();
		$this->assertSame( $expectedDecimals, $result );
	}

	public static function sanitiseDimensionProvider(): array {
		return [
			[
				'dimensionValue'   => '',
				'expectedValue'    => null,
				'numberOfDecimals' => 1,
				'unit'             => 'cm',
			],
			[
				'dimensionValue'   => 23.3567,
				'expectedValue'    => 234,
				'numberOfDecimals' => 1,
				'unit'             => 'cm',
			],
			[
				'dimensionValue'   => 10.0,
				'expectedValue'    => 100,
				'numberOfDecimals' => 1,
				'unit'             => 'cm',
			],
			[
				'dimensionValue'   => 0.100000000,
				'expectedValue'    => 1,
				'numberOfDecimals' => 1,
				'unit'             => 'cm',
			],
			[
				'dimensionValue'   => 200,
				'expectedValue'    => 200,
				'numberOfDecimals' => 0,
				'unit'             => 'mm',
			],
			[
				'dimensionValue'   => 200,
				'expectedValue'    => 200,
				'numberOfDecimals' => 0,
				'unit'             => 'mm',
			],
		];
	}

	/**
	 * @dataProvider sanitiseDimensionProvider
	 */
	public function testGetSanitizedDimensionValueInMm(
		float|int|string $dimensionValue,
		?int $expectedValue,
		int $numberOfDecimals,
		string $unit
	): void {
		$provider = $this->getMockBuilder( OptionsProvider::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getDimensionsNumberOfDecimals', 'getDimensionsUnit' ] )
			->getMock();

		$provider->method( 'getDimensionsNumberOfDecimals' )
			->willReturn( $numberOfDecimals );

		$provider->method( 'getDimensionsUnit' )
			->willReturn( $unit );

		$result = $provider->getSanitizedDimensionValueInMm( $dimensionValue );
		$this->assertEquals( $expectedValue, $result );
	}

	public function testStatusSyncingPacketStatuses(): void {
		$wpAdapterMock = $this->createMock( WpAdapter::class );
		$wpAdapterMock
			->method( 'getOption' )
			->willReturnCallback(
				static function ( string $key ): array {
					if ( $key === OptionNames::PACKETERY_SYNC ) {
						return [ 'status_syncing_packet_statuses' => [ PacketStatus::DEPARTED, PacketStatus::UNKNOWN ] ];
					}

					return [];
				}
			);

		$provider = new OptionsProvider(
			$wpAdapterMock
		);

		$result = $provider->getStatusSyncingPacketStatuses(
			[
				new PacketStatus( PacketStatus::DEPARTED, PacketStatus::DEPARTED, true ),
				new PacketStatus( PacketStatus::ARRIVED, PacketStatus::ARRIVED, true ),
			]
		);
		$this->assertSame( [ PacketStatus::DEPARTED ], $result );
	}

	public static function wcCarrierConfigEnabledNullableProvider(): array {
		return [
			[
				'inputValue'    => true,
				'expectedValue' => true,
			],
			[
				'inputValue'    => false,
				'expectedValue' => false,
			],
			[
				'inputValue'    => null,
				'expectedValue' => null,
			],
			[
				'inputValue'    => 1,
				'expectedValue' => true,
			],
			[
				'inputValue'    => 0,
				'expectedValue' => false,
			],
			[
				'inputValue'    => '1',
				'expectedValue' => true,
			],
			[
				'inputValue'    => '0',
				'expectedValue' => false,
			],
			[
				'inputValue'    => '',
				'expectedValue' => false,
			],
			[
				'inputValue'    => self::DUMMY_NONSENSE_VALUE,
				'expectedValue' => true,
			],
		];
	}

	/**
	 * @dataProvider wcCarrierConfigEnabledNullableProvider
	 */
	public function testIsWcCarrierConfigEnabledNullable( mixed $inputValue, ?bool $expectedValue ): void {
		$wpAdapterMock = $this->createMock( WpAdapter::class );
		$wpAdapterMock->method( 'getOption' )
						->willReturn( [ 'new_carrier_settings_enabled' => $inputValue ] );

		$provider = new OptionsProvider( $wpAdapterMock );

		$this->assertSame( $expectedValue, $provider->isWcCarrierConfigEnabledNullable() );
	}

	public static function autoEmailInfoInsertionEnabledProvider(): array {
		return [
			[
				'inputValue'    => true,
				'expectedValue' => true,
			],
			[
				'inputValue'    => false,
				'expectedValue' => false,
			],
			[
				'inputValue'    => null,
				'expectedValue' => true,
			],
			[
				'inputValue'    => 1,
				'expectedValue' => true,
			],
			[
				'inputValue'    => 0,
				'expectedValue' => false,
			],
			[
				'inputValue'    => '1',
				'expectedValue' => true,
			],
			[
				'inputValue'    => '0',
				'expectedValue' => false,
			],
			[
				'inputValue'    => '',
				'expectedValue' => false,
			],
			[
				'inputValue'    => self::DUMMY_NONSENSE_VALUE,
				'expectedValue' => true,
			],
		];
	}

	/**
	 * @dataProvider autoEmailInfoInsertionEnabledProvider
	 */
	public function testIsAutoEmailInfoInsertionEnabled( mixed $inputValue, bool $expectedValue ): void {
		$wpAdapterMock = $this->createMock( WpAdapter::class );
		$wpAdapterMock->method( 'getOption' )
						->willReturn( [ 'auto_email_info_insertion' => $inputValue ] );

		$provider = new OptionsProvider( $wpAdapterMock );

		$this->assertSame( $expectedValue, $provider->isAutoEmailInfoInsertionEnabled() );
	}

	public function testIsAutoEmailInfoInsertionEnabledReturnsDefaultWhenOptionNotSet(): void {
		$wpAdapterMock = $this->createMock( WpAdapter::class );
		$wpAdapterMock->method( 'getOption' )
						->willReturn( [] );

		$provider = new OptionsProvider( $wpAdapterMock );

		$this->assertSame( true, $provider->isAutoEmailInfoInsertionEnabled() );
	}

	public function testGetPacketAutoSubmissionMappedUniqueEvents(): void {
		$wpAdapterMock = $this->createMock( WpAdapter::class );
		$wpAdapterMock
			->method( 'getOption' )
			->willReturnCallback(
				static function ( string $key ): array {
					if ( $key === OptionNames::PACKETERY_AUTO_SUBMISSION ) {
						return [
							'payment_method_events' => [
								[
									'event' => 'order_paid',
								],
								[
									'event' => 'order_shipped',
								],
								[
									'event' => null,
								],
							],
						];
					}

					return [];
				}
			);

		$provider = new OptionsProvider( $wpAdapterMock );

		$result = $provider->getPacketAutoSubmissionMappedUniqueEvents();
		$this->assertSame( [ 'order_paid', 'order_shipped' ], array_values( $result ) );
	}

	public static function customCurrencyRatesProvider(): array {
		return [
			'not set'                  => [
				'currencyRatesData' => [],
				'expectedEnabled'   => false,
				'expectedRates'     => [],
				'requestedCurrency' => 'CZK',
				'expectedRate'      => null,
			],
			'enabled with rates'       => [
				'currencyRatesData' => [
					'enabled' => true,
					'rates'   => [
						'EUR' => 25.0,
						'CZK' => null,
					],
				],
				'expectedEnabled'   => true,
				'expectedRates'     => [
					'EUR' => 25.0,
					'CZK' => null,
				],
				'requestedCurrency' => 'EUR',
				'expectedRate'      => 25.0,
			],
			'enabled but missing rate' => [
				'currencyRatesData' => [
					'enabled' => true,
				],
				'expectedEnabled'   => true,
				'expectedRates'     => [],
				'requestedCurrency' => 'HUF',
				'expectedRate'      => null,
			],
			'disabled with rates'      => [
				'currencyRatesData' => [
					'enabled' => false,
					'rates'   => [
						'CZK' => 1.0,
					],
				],
				'expectedEnabled'   => false,
				'expectedRates'     => [
					'CZK' => 1.0,
				],
				'requestedCurrency' => 'CZK',
				'expectedRate'      => 1.0,
			],
			'rates not an array'       => [
				'currencyRatesData' => [
					'enabled' => true,
					'rates'   => 'not-an-array',
				],
				'expectedEnabled'   => true,
				'expectedRates'     => [],
				'requestedCurrency' => 'CZK',
				'expectedRate'      => null,
			],
			'enabled as string'        => [
				'currencyRatesData' => [
					'enabled' => '1',
					'rates'   => [
						'USD' => 20.0,
					],
				],
				'expectedEnabled'   => true,
				'expectedRates'     => [
					'USD' => 20.0,
				],
				'requestedCurrency' => 'CZK',
				'expectedRate'      => null,
			],
		];
	}

	/**
	 * @dataProvider customCurrencyRatesProvider
	 */
	public function testCustomCurrencyRates(
		array $currencyRatesData,
		bool $expectedEnabled,
		array $expectedRates,
		string $requestedCurrency,
		?float $expectedRate
	): void {
		$wpAdapterMock = $this->createMock( WpAdapter::class );
		$wpAdapterMock->method( 'getOption' )
			->willReturnCallback(
				static function ( string $key ) use ( $currencyRatesData ) {
					if ( $key === OptionNames::PACKETERY_CURRENCY_RATES ) {
						return $currencyRatesData;
					}

					return [];
				}
			);

		$provider = new OptionsProvider( $wpAdapterMock );

		$this->assertSame( $expectedEnabled, $provider->isCustomCurrencyRatesEnabled() );
		$this->assertSame( $expectedRates, $provider->getCustomCurrencyRates() );
		$this->assertSame( $expectedRate, $provider->getCustomCurrencyRate( $requestedCurrency ) );
	}
}
