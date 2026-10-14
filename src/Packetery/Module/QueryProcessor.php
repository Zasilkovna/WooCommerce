<?php
/**
 * Class QueryProcessor
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module;

use Packetery\Nette\Http\Request;

/**
 * Class QueryProcessor
 */
class QueryProcessor {

	private const FILTER_PARAMS = [
		'packetery_carrier_id',
		'packetery_to_submit',
		'packetery_to_print',
		'packetery_order_type',
	];

	/**
	 * Default WooCommerce search filter. It does not change the search results.
	 * Any extra query argument makes WooCommerce count the filtered orders instead of using the cached count.
	 */
	private const SEARCH_FILTER_ALL = 'all';

	/**
	 * HTTP request.
	 *
	 * @var Request
	 */
	private $httpRequest;

	/**
	 * Order repository.
	 *
	 * @var \Packetery\Module\Order\Repository
	 */
	private $orderRepository;

	/**
	 * Context resolver.
	 *
	 * @var ContextResolver
	 */
	private $contextResolver;

	/**
	 * Constructor.
	 *
	 * @param Request          $httpRequest     HTTP request.
	 * @param Order\Repository $orderRepository Order repository.
	 * @param ContextResolver  $contextResolver Context resolver.
	 */
	public function __construct( Request $httpRequest, Order\Repository $orderRepository, ContextResolver $contextResolver ) {
		$this->httpRequest     = $httpRequest;
		$this->orderRepository = $orderRepository;
		$this->contextResolver = $contextResolver;
	}

	/**
	 * Registers service.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'posts_clauses', [ $this, 'processClauses' ], 10, 2 );
		add_filter( 'woocommerce_orders_table_query_clauses', [ $this, 'processHposClauses' ] );
		add_filter( 'woocommerce_shop_order_list_table_request', [ $this, 'processHposRequest' ] );
		add_filter( 'woocommerce_shop_order_list_table_prepare_items_query_args', [ $this, 'processHposQueryArgs' ] );
	}

	/**
	 * Extends WP_Query to include custom table.
	 *
	 * @link https://wordpress.stackexchange.com/questions/50305/how-to-extend-wp-query-to-include-custom-table-in-query
	 *
	 * @param array<string, string>|mixed $clauses     Clauses.
	 * @param \WP_Query|mixed             $queryObject WP_Query.
	 *
	 * @return array<string, string>|mixed
	 */
	public function processClauses( $clauses, $queryObject ) {
		if ( $this->contextResolver->isOrderGridPage() === false ) {
			return $clauses;
		}

		if ( ! is_array( $clauses )
			|| array_filter(
				$clauses,
				static function ( $value, $key ) {
						return is_string( $key ) && is_string( $value );
				},
				ARRAY_FILTER_USE_BOTH
			) !== $clauses
		) {
			WcLogger::logArgumentTypeError( __METHOD__, 'clauses', 'array', $clauses );

			return $clauses;
		}

		if ( ! $queryObject instanceof \WP_Query ) {
			WcLogger::logArgumentTypeError( __METHOD__, 'queryObject', \WP_Query::class, $queryObject );

			return $clauses;
		}

		$isOrderPostQueryCall =
			isset( $queryObject->query['post_type'] ) &&
			(
				$queryObject->query['post_type'] === 'shop_order' ||
				( is_array( $queryObject->query['post_type'] ) && in_array( 'shop_order', $queryObject->query['post_type'], true ) )
			);
		if ( $isOrderPostQueryCall === false ) {
			return $clauses;
		}

		return $this->orderRepository->processClauses(
			$clauses,
			$queryObject,
			$this->getParamValues()
		);
	}

	/**
	 * Extends High-Performance order storage grid filters.
	 *
	 * @param array<string, string> $clauses Clauses.
	 *
	 * @return array<string, string>
	 */
	public function processHposClauses( array $clauses ): array {
		if ( $this->contextResolver->isOrderGridPage() === false ) {
			return $clauses;
		}

		return $this->orderRepository->processClauses(
			$clauses,
			null,
			$this->getParamValues()
		);
	}

	/**
	 * Adds the default search filter to the order list request when a Packeta filter is active.
	 *
	 * @param array<string, string>|mixed $request Order list request.
	 *
	 * @return array<string, string>|mixed
	 */
	public function processHposRequest( $request ) {
		if ( ! is_array( $request ) || ( $request['search-filter'] ?? '' ) !== '' || ! $this->hasActiveFilter() ) {
			return $request;
		}

		$request['search-filter'] = self::SEARCH_FILTER_ALL;

		return $request;
	}

	/**
	 * Adds the default search filter to the order query arguments when a Packeta filter is active.
	 *
	 * @param array<string, mixed>|mixed $queryArgs Order query arguments.
	 *
	 * @return array<string, mixed>|mixed
	 */
	public function processHposQueryArgs( $queryArgs ) {
		if ( ! is_array( $queryArgs ) || isset( $queryArgs['search_filter'] ) || ! $this->hasActiveFilter() ) {
			return $queryArgs;
		}

		$queryArgs['search_filter'] = self::SEARCH_FILTER_ALL;

		return $queryArgs;
	}

	/**
	 * Tells if at least one Packeta order list filter has a value.
	 *
	 * @return bool
	 */
	private function hasActiveFilter(): bool {
		foreach ( self::FILTER_PARAMS as $param ) {
			if ( ! in_array( $this->httpRequest->getQuery( $param ), [ null, '' ], true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Gets param values.
	 *
	 * @return array<string,null>
	 */
	private function getParamValues(): array {
		$paramValues = [];

		foreach ( array_merge( self::FILTER_PARAMS, [ 'orderby', 'order' ] ) as $key ) {
			$paramValues[ $key ] = $this->httpRequest->getQuery( $key );
		}

		return $paramValues;
	}
}
