<?php
/**
 * Class Controller
 *
 * @package Packetery\Module\Order
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Module\Order;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Class Controller
 *
 * @package Packetery\Module\Order
 */
class Controller extends WP_REST_Controller {

	public const PATH_SAVE_MODAL = '/save';

	/**
	 * Order modal.
	 *
	 * @var Modal
	 */
	private $orderModal;

	/**
	 * @var ControllerRouter
	 */
	private $router;

	/**
	 * Controller constructor.
	 *
	 * @param Modal $orderModal
	 */
	public function __construct( Modal $orderModal, ControllerRouter $controllerRouter ) {
		$this->orderModal = $orderModal;
		$this->router = $controllerRouter;
		$this->namespace = $controllerRouter->getNamespace();
		$this->rest_base = $controllerRouter->getRestBase();
	}

	/**
	 * Register the routes of the controller.
	 *
	 * @return void
	 */
	public function registerRoutes(): void {
		$this->router->registerRoute( self::PATH_SAVE_MODAL, [
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'saveModal' ],
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			],
		] );
	}

	/**
	 * Update one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function saveModal( $request ) {
		$data            = [];
		$parameters      = $request->get_body_params();
		$packeteryWeight = $parameters['packeteryWeight'];
		$orderId         = $parameters['orderId'];

		$form = $this->orderModal->createForm();
		$form->setValues([
			Order\Entity::META_WEIGHT => $packeteryWeight
		]);

		if ( false === $form->isValid()) {
			return new WP_Error( 'form_invalid', implode( ', ', $form->getErrors() ), 400 );
		}

		$packeteryWeightTransformed = $form[Order\Entity::META_WEIGHT]->getValue();
		update_post_meta( $orderId, Order\Entity::META_WEIGHT, $packeteryWeightTransformed );

		$data['message'] = __( 'Success', 'packetery' );
		$data['data'] = [
			Order\Entity::META_WEIGHT => $packeteryWeightTransformed
		];

		return new WP_REST_Response( $data, 200 );
	}
}
