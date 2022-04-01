<?php
/**
 * Class Controller
 *
 * @package Packetery\Module\Order
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Helper;
use Packetery\Module\Calculator;
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
	 * Order repository.
	 *
	 * @var Repository
	 */
	private $orderRepository;

	/**
	 * Calculator.
	 *
	 * @var Calculator
	 */
	private $calculator;

	/**
	 * Controller constructor.
	 *
	 * @param Modal            $orderModal       Modal.
	 * @param ControllerRouter $controllerRouter Router.
	 * @param PacketSubmitter  $packetSubmitter  Packet submitter.
	 * @param Order\Repository $orderRepository  Order repository.
	 * @param Calculator       $calculator       Calculator.
	 */
	public function __construct(
		Modal $orderModal,
		ControllerRouter $controllerRouter,
		PacketSubmitter $packetSubmitter,
		Order\Repository $orderRepository,
		Calculator $calculator
	) {
		$this->orderModal      = $orderModal;
		$this->router          = $controllerRouter;
		$this->namespace       = $controllerRouter->getNamespace();
		$this->rest_base       = $controllerRouter->getRestBase();
		$this->packetSubmitter = $packetSubmitter;
		$this->orderRepository = $orderRepository;
		$this->calculator      = $calculator;
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
		if ( false === $order ) {
			return new WP_Error( 'order_not_loaded', __( 'Order could not be loaded.', 'packetery' ), 400 );
		}
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
				'packetery_weight' => $packeteryWeight,
			]
		);

		if ( false === $form->isValid() ) {
			return new WP_Error( 'form_invalid', implode( ', ', $form->getErrors() ), 400 );
		}

		$values = $form->getValues( 'array' );
		if ( ! is_numeric( $values['packetery_weight'] ) ) {
			$wcOrder = wc_get_order( $orderId );
			if ( false !== $wcOrder ) {
				$values['packetery_weight'] = $this->calculator->calculateOrderWeight( $wcOrder );
			}
		}

		$order = $this->orderRepository->getById( $orderId );
		if ( null === $order ) {
			return new WP_Error( 'order_not_loaded', __( 'Order could not be loaded.', 'packetery' ), 400 );
		}
		$order->setWeight( Helper::simplifyWeight( $values['packetery_weight'] ) );
		$this->orderRepository->save( $order );

		$data['message'] = __( 'Success', 'packetery' );
		$data['data']    = [
			'packetery_weight' => $values['packetery_weight'],
		];

		return new WP_REST_Response( $data, 200 );
	}
}
