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
	}

	/**
	 * Extends WP_Query to include custom table.
	 *
	 * @link https://wordpress.stackexchange.com/questions/50305/how-to-extend-wp-query-to-include-custom-table-in-query
	 *
	 * @param array<string, string> $clauses     Clauses.
	 * @param \WP_Query             $queryObject WP_Query.
	 *
	 * @return array<string, string>
	 */
	public function processClauses( array $clauses, \WP_Query $queryObject ): array {
		if ( $this->contextResolver->isOrderGridPage() === false ) {
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
	 * Gets param values.
	 *
	 * @return array<string,null>
	 */
	private function getParamValues(): array {
		$paramValues = [
			'packetery_carrier_id' => null,
			'packetery_to_submit'  => null,
			'packetery_to_print'   => null,
			'packetery_order_type' => null,
			'orderby'              => null,
			'order'                => null,
		];

		foreach ( $paramValues as $key => $value ) {
			$paramValues[ $key ] = $this->httpRequest->getQuery( $key );
		}

		return $paramValues;
	}
}
