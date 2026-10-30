<?php

declare( strict_types=1 );

namespace Tests\Module\Shipping;

use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Shipping\BaseShippingMethod;
use Packetery\Module\Shipping\ShippingMethodOptionsPrimer;
use Packetery\Module\WpdbAdapter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShippingMethodOptionsPrimerTest extends TestCase {
	private const ZONE_METHODS_TABLE    = 'wp_woocommerce_shipping_zone_methods';
	private const OPTIONS_TABLE         = 'wp_options';
	private const ZONE_METHODS_QUERY    = 'prepared zone methods query';
	private const ESCAPED_PREFIX        = 'packeta\_method\_';
	private const OPTION_AUTOLOADED     = 'woocommerce_packeta_method_106_1_settings';
	private const OPTION_SAVED          = 'woocommerce_packeta_method_131_2_settings';
	private const OPTION_MISSING        = 'woocommerce_packeta_method_4307_3_settings';
	private const OPTION_CACHED         = 'woocommerce_packeta_method_zpointcz_4_settings';
	private const OPTION_KNOWN_MISSING  = 'woocommerce_packeta_method_106_5_settings';
	private const OPTION_CARRIER        = 'packetery_carrier_106';
	private const OPTION_SAVED_RAW      = 'a:1:{s:5:"title";s:4:"Test";}';
	private const OPTIONS_IN_CLAUSE     = "'quoted names'";
	private const FOREIGN_MISSING_NAME  = 'some_other_option';
	private const OPTIONS_CACHE_GROUP   = 'options';
	private const NOT_OPTIONS_CACHE_KEY = 'notoptions';

	private WpdbAdapter&MockObject $wpdbAdapter;
	private WpAdapter&MockObject $wpAdapter;

	/**
	 * @var string[]
	 */
	private array $queries = [];

	/**
	 * @param array<int, array{method_id: string, instance_id: string}>    $zoneMethodRows
	 * @param array<int, array{option_name: string, option_value: string}> $optionRows
	 */
	private function createPrimer( array $zoneMethodRows, array $optionRows = [] ): ShippingMethodOptionsPrimer {
		$this->wpdbAdapter = $this->createMock( WpdbAdapter::class );
		$this->wpAdapter   = $this->createMock( WpAdapter::class );

		$this->wpdbAdapter
			->method( 'getShippingZoneMethodsTable' )
			->willReturn( self::ZONE_METHODS_TABLE );
		$this->wpdbAdapter
			->method( 'getOptionsTable' )
			->willReturn( self::OPTIONS_TABLE );
		$this->wpdbAdapter
			->method( 'escLike' )
			->with( BaseShippingMethod::PACKETA_METHOD_PREFIX )
			->willReturn( self::ESCAPED_PREFIX );
		$this->wpdbAdapter
			->method( 'prepare' )
			->with(
				'SELECT `method_id`, `instance_id` FROM `' . self::ZONE_METHODS_TABLE . '` WHERE `method_id` LIKE %s',
				self::ESCAPED_PREFIX . '%'
			)
			->willReturn( self::ZONE_METHODS_QUERY );
		$this->wpdbAdapter
			->method( 'get_results' )
			->willReturnCallback(
				function ( string $query ) use ( $zoneMethodRows, $optionRows ): array {
					$this->queries[] = $query;
					$rows            = $query === self::ZONE_METHODS_QUERY ? $zoneMethodRows : $optionRows;

					return array_map(
						function ( array $row ): object {
							return (object) $row;
						},
						$rows
					);
				}
			);

		return new ShippingMethodOptionsPrimer( $this->wpdbAdapter, $this->wpAdapter );
	}

	/**
	 * @return array<int, array{method_id: string, instance_id: string}>
	 */
	private static function getZoneMethodRows(): array {
		return [
			[
				'method_id'   => 'packeta_method_106',
				'instance_id' => '1',
			],
			[
				'method_id'   => 'packeta_method_131',
				'instance_id' => '2',
			],
			[
				'method_id'   => 'packeta_method_4307',
				'instance_id' => '3',
			],
		];
	}

	/**
	 * @return array<string, array{
	 *     zoneMethodRows: array<int, array{method_id: string, instance_id: string}>,
	 *     givenOptionNames: string[],
	 *     expectedOptionNames: string[]
	 * }>
	 */
	public static function optionNamesProvider(): array {
		return [
			'zone methods only'        => [
				'zoneMethodRows'      => self::getZoneMethodRows(),
				'givenOptionNames'    => [],
				'expectedOptionNames' => [ self::OPTION_AUTOLOADED, self::OPTION_SAVED, self::OPTION_MISSING ],
			],
			'given options only'       => [
				'zoneMethodRows'      => [],
				'givenOptionNames'    => [ self::OPTION_CARRIER ],
				'expectedOptionNames' => [ self::OPTION_CARRIER ],
			],
			'zone methods and options' => [
				'zoneMethodRows'      => self::getZoneMethodRows(),
				'givenOptionNames'    => [ self::OPTION_CARRIER ],
				'expectedOptionNames' => [
					self::OPTION_AUTOLOADED,
					self::OPTION_SAVED,
					self::OPTION_MISSING,
					self::OPTION_CARRIER,
				],
			],
		];
	}

	/**
	 * @dataProvider optionNamesProvider
	 *
	 * @param array<int, array{method_id: string, instance_id: string}> $zoneMethodRows
	 * @param string[]                                                  $givenOptionNames
	 * @param string[]                                                  $expectedOptionNames
	 */
	public function testPrimeUsesWordPressFunctionWithOptionNamesOfZoneMethodsAndGivenOptions(
		array $zoneMethodRows,
		array $givenOptionNames,
		array $expectedOptionNames
	): void {
		$primer = $this->createPrimer( $zoneMethodRows );
		$this->wpAdapter
			->method( 'canPrimeOptionCaches' )
			->willReturn( true );
		$this->wpAdapter
			->expects( $this->once() )
			->method( 'primeOptionCaches' )
			->with( $expectedOptionNames );
		$this->wpAdapter
			->expects( $this->never() )
			->method( 'cacheAdd' );

		$primer->prime( $givenOptionNames );

		self::assertSame( [ self::ZONE_METHODS_QUERY ], $this->queries );
	}

	/**
	 * @return array<string, array{blogIds: int[], expectedPrimeCount: int}>
	 */
	public static function blogIdsProvider(): array {
		return [
			'same blog'     => [
				'blogIds'            => [ 1, 1 ],
				'expectedPrimeCount' => 1,
			],
			'switched blog' => [
				'blogIds'            => [ 1, 2 ],
				'expectedPrimeCount' => 2,
			],
		];
	}

	/**
	 * @dataProvider blogIdsProvider
	 *
	 * @param int[] $blogIds Current blog ID for each call.
	 */
	public function testPrimeRunsOncePerBlog( array $blogIds, int $expectedPrimeCount ): void {
		$primer = $this->createPrimer( self::getZoneMethodRows() );
		$this->wpAdapter
			->method( 'getCurrentBlogId' )
			->willReturnOnConsecutiveCalls( ...$blogIds );
		$this->wpAdapter
			->method( 'canPrimeOptionCaches' )
			->willReturn( true );
		$this->wpAdapter
			->expects( $this->exactly( $expectedPrimeCount ) )
			->method( 'primeOptionCaches' );

		$callCount = count( $blogIds );
		for ( $call = 0; $call < $callCount; $call++ ) {
			$primer->prime( [] );
		}

		self::assertSame( array_fill( 0, $expectedPrimeCount, self::ZONE_METHODS_QUERY ), $this->queries );
	}

	public function testPrimeWithoutPacketaZoneMethodsLoadsNoOptions(): void {
		$primer = $this->createPrimer( [] );
		$this->wpAdapter
			->expects( $this->never() )
			->method( 'primeOptionCaches' );
		$this->wpAdapter
			->expects( $this->never() )
			->method( 'loadAllOptions' );

		$primer->prime( [] );

		self::assertSame( [ self::ZONE_METHODS_QUERY ], $this->queries );
	}

	public function testPrimeWithoutWordPressFunctionCachesFoundAndMissingOptions(): void {
		$zoneMethodRows   = self::getZoneMethodRows();
		$zoneMethodRows[] = [
			'method_id'   => 'packeta_method_zpointcz',
			'instance_id' => '4',
		];
		$zoneMethodRows[] = [
			'method_id'   => 'packeta_method_106',
			'instance_id' => '5',
		];
		$primer           = $this->createPrimer(
			$zoneMethodRows,
			[
				[
					'option_name'  => self::OPTION_SAVED,
					'option_value' => self::OPTION_SAVED_RAW,
				],
			]
		);
		$this->mockCacheWithoutWordPressFunction(
			[
				self::OPTION_AUTOLOADED,
				self::OPTION_SAVED,
				self::OPTION_MISSING,
				self::OPTION_CACHED,
				self::OPTION_KNOWN_MISSING,
			],
			[ self::OPTION_AUTOLOADED => [ 'title' => 'Autoloaded' ] ],
			[
				self::OPTION_SAVED         => false,
				self::OPTION_MISSING       => false,
				self::OPTION_CACHED        => self::OPTION_SAVED_RAW,
				self::OPTION_KNOWN_MISSING => false,
			],
			[
				self::OPTION_KNOWN_MISSING => true,
				self::FOREIGN_MISSING_NAME => true,
			]
		);
		$this->wpdbAdapter
			->expects( $this->once() )
			->method( 'prepareInClause' )
			->with( [ self::OPTION_SAVED, self::OPTION_MISSING ] )
			->willReturn( self::OPTIONS_IN_CLAUSE );
		$this->wpAdapter
			->expects( $this->once() )
			->method( 'cacheAdd' )
			->with( self::OPTION_SAVED, self::OPTION_SAVED_RAW, self::OPTIONS_CACHE_GROUP );
		$this->wpAdapter
			->expects( $this->once() )
			->method( 'cacheSet' )
			->with(
				self::NOT_OPTIONS_CACHE_KEY,
				[
					self::OPTION_KNOWN_MISSING => true,
					self::FOREIGN_MISSING_NAME => true,
					self::OPTION_MISSING       => true,
				],
				self::OPTIONS_CACHE_GROUP
			);

		$primer->prime( [] );

		self::assertSame(
			[
				self::ZONE_METHODS_QUERY,
				'SELECT `option_name`, `option_value` FROM `' . self::OPTIONS_TABLE . '`' .
					' WHERE `option_name` IN (' . self::OPTIONS_IN_CLAUSE . ')',
			],
			$this->queries
		);
	}

	public function testPrimeWithoutWordPressFunctionSkipsQueryWhenAllOptionsAreAutoloaded(): void {
		$primer = $this->createPrimer( array_slice( self::getZoneMethodRows(), 0, 1 ) );
		$this->mockCacheWithoutWordPressFunction(
			[ self::OPTION_AUTOLOADED ],
			[ self::OPTION_AUTOLOADED => [] ],
			[],
			false
		);
		$this->wpAdapter
			->expects( $this->never() )
			->method( 'cacheSet' );

		$primer->prime( [] );

		self::assertSame( [ self::ZONE_METHODS_QUERY ], $this->queries );
	}

	public function testPrimeWithoutWordPressFunctionKeepsNotOptionsWhenAllOptionsExist(): void {
		$primer = $this->createPrimer(
			array_slice( self::getZoneMethodRows(), 1, 1 ),
			[
				[
					'option_name'  => self::OPTION_SAVED,
					'option_value' => self::OPTION_SAVED_RAW,
				],
			]
		);
		$this->mockCacheWithoutWordPressFunction(
			[ self::OPTION_SAVED ],
			[],
			[ self::OPTION_SAVED => false ],
			false
		);
		$this->wpAdapter
			->expects( $this->once() )
			->method( 'cacheAdd' );
		$this->wpAdapter
			->expects( $this->never() )
			->method( 'cacheSet' );

		$primer->prime( [] );

		self::assertCount( 2, $this->queries );
	}

	/**
	 * @param string[]                  $optionNames Names which the primer looks up in the cache.
	 * @param array<string, mixed>      $allOptions
	 * @param array<string, mixed>      $cachedOptions
	 * @param array<string, bool>|false $notOptions
	 */
	private function mockCacheWithoutWordPressFunction(
		array $optionNames,
		array $allOptions,
		array $cachedOptions,
		$notOptions
	): void {
		$this->wpAdapter
			->method( 'canPrimeOptionCaches' )
			->willReturn( false );
		$this->wpAdapter
			->expects( $this->never() )
			->method( 'primeOptionCaches' );
		$this->wpAdapter
			->method( 'loadAllOptions' )
			->willReturn( $allOptions );
		$this->wpAdapter
			->method( 'cacheGetMultiple' )
			->with( $optionNames, self::OPTIONS_CACHE_GROUP )
			->willReturn( $cachedOptions );
		$this->wpAdapter
			->method( 'cacheGet' )
			->with( self::NOT_OPTIONS_CACHE_KEY, self::OPTIONS_CACHE_GROUP )
			->willReturn( $notOptions );
	}
}
