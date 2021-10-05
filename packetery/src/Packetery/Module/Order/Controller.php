<?php

declare( strict_types=1 );

namespace Packetery\Module\Order;

use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class Controller extends WP_REST_Controller {

	/**
	 * @var string
	 */
	protected $namespace = 'packetery/v1';

	/**
	 * @var string
	 */
	protected $rest_base = 'order';

	/**
	 * Register the routes of the controller.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, "/{$this->rest_base}/(?P<id>[\\d]+)", array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'permission_callback' ),
			),
		) );

		register_rest_route( $this->namespace, "/{$this->rest_base}/ping", array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => function () {
				return microtime();
			},
			'permission_callback' => array( $this, 'permission_callback' ),
		) );
	}

	public function permission_callback(): bool {
		return true;
	}

	/**
	 * Update one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {
		$data            = [];
		$orderId         = $request->get_param( 'id' );
		$parameters      = $request->get_body_params();
		$packeteryWeight = $parameters['packeteryWeight'];

		if ( false === is_numeric( $orderId ) ) {
			return new WP_Error( 'could_not_update', __( 'error', 'packetery' ), [ 'status' => 500 ] );
		}

		update_post_meta( $orderId, Entity::META_WEIGHT, $packeteryWeight );

		$data['type']    = 'success';
		$data['message'] = __( 'Success', 'packetery' );

		return new WP_REST_Response( $data, 200 );
	}
}
