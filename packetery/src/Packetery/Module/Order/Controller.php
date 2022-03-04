<?php
/**
 * Class Controller
 *
 * @package Packetery\Module\Order
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Module\EntityFactory;
use Packetery\Module\Order;
use Packetery\Core\Helper;
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

	public const PATH_SAVE_MODAL    = '/save';
	public const PATH_SUBMIT_TO_API = '/submit-to-api';

	/**
	 * Order modal.
	 *
	 * @var Modal
	 */
	private $orderModal;

	/**
	 * Router.
	 *
	 * @var ControllerRouter
	 */
	private $router;

	/**
	 * Packet submitter.
	 *
	 * @var PacketSubmitter
	 */
	private $packetSubmitter;

	/**
	 * Order factory.
	 *
	 * @var EntityFactory\Order
	 */
	private $orderFactory;

	/**
	 * Order repository.
	 *
	 * @var Repository
	 */
	private $orderRepository;

	/**
	 * Controller constructor.
	 *
	 * @param Modal               $orderModal       Modal.
	 * @param ControllerRouter    $controllerRouter Router.
	 * @param PacketSubmitter     $packetSubmitter  Packet submitter.
	 * @param EntityFactory\Order $orderFactory     Order factory.
	 * @param Order\Repository    $orderRepository  Order repository.
	 */
	public function __construct(
		Modal $orderModal,
		ControllerRouter $controllerRouter,
		PacketSubmitter $packetSubmitter,
		EntityFactory\Order $orderFactory,
		Order\Repository $orderRepository
	) {
		$this->orderModal      = $orderModal;
		$this->router          = $controllerRouter;
		$this->namespace       = $controllerRouter->getNamespace();
		$this->rest_base       = $controllerRouter->getRestBase();
		$this->packetSubmitter = $packetSubmitter;
		$this->orderFactory    = $orderFactory;
		$this->orderRepository = $orderRepository;
	}

	/**
	 * Register the routes of the controller.
	 *
	 * @return void
	 */
	public function registerRoutes(): void {
		$this->router->registerRoute(
			self::PATH_SAVE_MODAL,
			[
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'saveModal' ],
					'permission_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
				],
			]
		);
		$this->router->registerRoute(
			self::PATH_SUBMIT_TO_API,
			[
				[
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => [ $this, 'submitToApi' ],
					'permission_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
				],
			]
		);
	}

	/**
	 * Submit packet to API.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function submitToApi( $request ) {
		$data       = [];
		$parameters = $request->get_body_params();
		$orderId    = $parameters['orderId'];
		$order      = wc_get_order( $orderId );

		$resultsCounter = [
			'success' => 0,
			'ignored' => 0,
			'errors'  => 0,
		];
		$this->packetSubmitter->submitPacket( $order, $resultsCounter );
		$data['redirectTo'] = add_query_arg(
			[
				'post_type'     => 'shop_order',
				'submit_to_api' => '1',
			] + $resultsCounter,
			admin_url( 'edit.php' )
		);

		return new WP_REST_Response( $data, 200 );
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
		$orderId         = (int) $parameters['orderId'];

		$form = $this->orderModal->createForm();
		$form->setValues(
			[
				Order\Entity::META_WEIGHT => $packeteryWeight,
			]
		);

		if ( false === $form->isValid() ) {
			return new WP_Error( 'form_invalid', implode( ', ', $form->getErrors() ), 400 );
		}

		$values = $form->getValues( 'array' );
		if ( ! is_numeric( $values[ Order\Entity::META_WEIGHT ] ) ) {
			$values[ Order\Entity::META_WEIGHT ] = $this->orderFactory->calculateOrderWeight( wc_get_order( $orderId ) );
		}

		$this->orderRepository->update( [ Order\Entity::META_WEIGHT => Helper::simplifyWeight( $values[ Order\Entity::META_WEIGHT ] ) ], $orderId );

		$data['message'] = __( 'Success', 'packetery' );
		$data['data']    = [
			Order\Entity::META_WEIGHT => $values[ Order\Entity::META_WEIGHT ],
		];

		return new WP_REST_Response( $data, 200 );
	}
}
