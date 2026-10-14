<?php

declare( strict_types=1 );

namespace Tests\Module;

use Packetery\Module\ContextResolver;
use Packetery\Module\Order\Repository;
use Packetery\Module\QueryProcessor;
use Packetery\Nette\Http\Request;
use PHPUnit\Framework\TestCase;

class QueryProcessorTest extends TestCase {

	private const ACTIVE_FILTERS = [
		'packetery_carrier_id' => '106',
		'packetery_to_submit'  => '1',
		'packetery_to_print'   => '1',
		'packetery_order_type' => 'zpoint',
	];

	private const ACTIVE_FILTER = [ 'packetery_to_submit' => '1' ];
	private const EMPTY_FILTER  = [ 'packetery_to_print' => '' ];

	private const QUERY_ARGS = [
		'limit'    => 20,
		'page'     => 1,
		'paginate' => true,
		'type'     => 'shop_order',
	];

	private const ALL   = 'all';
	private const EMAIL = 'customer_email';

	/**
	 * @return array<string, array{array<string, string>, array<string, mixed>|string, array<string, mixed>|string}>
	 */
	public static function processHposRequestProvider(): array {
		$request = [
			's'             => '',
			'search-filter' => '',
		];

		return array_merge(
			self::activeFilterCases( $request, array_merge( $request, [ 'search-filter' => self::ALL ] ) ),
			[
				'active filter keeps search filter'      => [
					self::ACTIVE_FILTER,
					array_merge( $request, [ 'search-filter' => self::EMAIL ] ),
					array_merge( $request, [ 'search-filter' => self::EMAIL ] ),
				],
				'empty filter keeps empty search filter' => [ self::EMPTY_FILTER, $request, $request ],
				'no filter keeps empty search filter'    => [ [], $request, $request ],
				'invalid request stays unchanged'        => [ self::ACTIVE_FILTER, 'invalid', 'invalid' ],
			]
		);
	}

	/**
	 * @dataProvider processHposRequestProvider
	 *
	 * @param array<string, string>        $queryParams
	 * @param array<string, string>|string $request
	 * @param array<string, string>|string $expectedRequest
	 */
	public function testProcessHposRequest( array $queryParams, $request, $expectedRequest ): void {
		$queryProcessor = $this->createQueryProcessor( $queryParams );

		self::assertSame( $expectedRequest, $queryProcessor->processHposRequest( $request ) );
	}

	/**
	 * @return array<string, array{array<string, string>, array<string, mixed>|string, array<string, mixed>|string}>
	 */
	public static function processHposQueryArgsProvider(): array {
		$withEmailFilter = self::QUERY_ARGS + [ 'search_filter' => self::EMAIL ];

		return array_merge(
			self::activeFilterCases( self::QUERY_ARGS, self::QUERY_ARGS + [ 'search_filter' => self::ALL ] ),
			[
				'active filter keeps search filter' => [ self::ACTIVE_FILTER, $withEmailFilter, $withEmailFilter ],
				'empty filter keeps query args'     => [ self::EMPTY_FILTER, self::QUERY_ARGS, self::QUERY_ARGS ],
				'no filter keeps query args'        => [ [], self::QUERY_ARGS, self::QUERY_ARGS ],
				'invalid query args stay unchanged' => [ self::ACTIVE_FILTER, 'invalid', 'invalid' ],
			]
		);
	}

	/**
	 * @dataProvider processHposQueryArgsProvider
	 *
	 * @param array<string, string>       $queryParams
	 * @param array<string, mixed>|string $queryArgs
	 * @param array<string, mixed>|string $expectedQueryArgs
	 */
	public function testProcessHposQueryArgs( array $queryParams, $queryArgs, $expectedQueryArgs ): void {
		$queryProcessor = $this->createQueryProcessor( $queryParams );

		self::assertSame( $expectedQueryArgs, $queryProcessor->processHposQueryArgs( $queryArgs ) );
	}

	/**
	 * @param array<string, mixed> $input
	 * @param array<string, mixed> $expected
	 *
	 * @return array<string, array{array<string, string>, array<string, mixed>, array<string, mixed>}>
	 */
	private static function activeFilterCases( array $input, array $expected ): array {
		$cases = [];
		foreach ( self::ACTIVE_FILTERS as $param => $value ) {
			$cases[ "{$param} filter adds search filter" ] = [ [ $param => $value ], $input, $expected ];
		}

		return $cases;
	}

	/**
	 * @param array<string, string> $queryParams
	 */
	private function createQueryProcessor( array $queryParams ): QueryProcessor {
		$httpRequest = $this->createMock( Request::class );
		$httpRequest
			->method( 'getQuery' )
			->willReturnCallback(
				static function ( string $key ) use ( $queryParams ) {
					return $queryParams[ $key ] ?? null;
				}
			);

		return new QueryProcessor(
			$httpRequest,
			$this->createMock( Repository::class ),
			$this->createMock( ContextResolver::class )
		);
	}
}
