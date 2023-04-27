<?php
/**
 * Class QueryProcessor
 *
 * @package Packetery
 */

declare( strict_types=1 );


namespace Packetery\Module;

use PacketeryNette\Http\Request;

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
	 * Constructor.
	 *
	 * @param Request          $httpRequest     HTTP request.
	 * @param Order\Repository $orderRepository Order repository.
	 */
	public function __construct( Request $httpRequest, Order\Repository $orderRepository ) {
		$this->httpRequest     = $httpRequest;
		$this->orderRepository = $orderRepository;
	}

	/**
	 * Registers service.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'posts_clauses', [ $this, 'processPostClauses' ], 10, 2 );
	}

	/**
	 * Extends WP_Query to include custom table.
	 *
	 * @link https://wordpress.stackexchange.com/questions/50305/how-to-extend-wp-query-to-include-custom-table-in-query
	 *
	 * @param array|mixed     $clauses     Clauses.
	 * @param \WP_Query|mixed $queryObject WP_Query.
	 *
	 * @return array|mixed
	 */
	public function processPostClauses( $clauses, $queryObject ) {
		if ( ! is_array( $clauses ) ) {
			WcLogger::logArgumentTypeError( __METHOD__, 'clauses', 'array', $clauses );
			return $clauses;
		}

		if ( ! $queryObject instanceof \WP_Query ) {
			WcLogger::logArgumentTypeError( __METHOD__, 'queryObject', \WP_Query::class, $queryObject );
			return $clauses;
		}

		$paramValues = [
			'packetery_carrier_id' => null,
			'packetery_to_submit'  => null,
			'packetery_to_print'   => null,
			'packetery_order_type' => null,
		];

		foreach ( $paramValues as $key => $value ) {
			$paramValues[ $key ] = $this->getParamValue( $queryObject, $key );
		}

		return $this->orderRepository->processPostClauses( $clauses, $queryObject, $paramValues );
	}

	/**
	 * Gets parameter value from GET data or WP_Query.
	 *
	 * @param \WP_Query $queryObject WP_Query.
	 * @param string    $key Key.
	 *
	 * @return mixed|null
	 */
	private function getParamValue( \WP_Query $queryObject, string $key ) {
		$get = $this->httpRequest->getQuery();
		if ( isset( $get[ $key ] ) && '' !== (string) $get[ $key ] ) {
			return $get[ $key ];
		}
		if ( isset( $queryObject->query[ $key ] ) && '' !== (string) $queryObject->query[ $key ] ) {
			return $queryObject->query[ $key ];
		}

		return null;
	}
}
