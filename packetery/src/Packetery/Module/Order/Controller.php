<?php

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Module\Order;
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
	 *
	 * @return void
	 */
	public function registerRoutes(): void {
		register_rest_route( $this->namespace, "/{$this->rest_base}/save", [
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'updateItem' ],
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			],
		] );
	}

	/**
	 * Gets controller route.
	 *
	 * @param string $route
	 *
	 * @return string
	 */
	public function getRoute( string $route ): string {
		return get_rest_url( null, $this->namespace . "/{$this->rest_base}{$route}" );
	}

	/**
	 * Update one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function updateItem( $request ) {
		$data            = [];
		$parameters      = $request->get_body_params();
		$packeteryWeight = $parameters['packeteryWeight'];
		$orderId         = $parameters['orderId'];

		update_post_meta( $orderId, Order\Entity::META_WEIGHT, $packeteryWeight );

		$data['message'] = __( 'Success', 'packetery' );

		return new WP_REST_Response( $data, 200 );
	}
}
