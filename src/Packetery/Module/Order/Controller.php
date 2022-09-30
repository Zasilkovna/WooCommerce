<?php
/**
 * Class Controller
 *
 * @package Packetery\Module\Order
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Entity\Size;
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
	 * Grid extender service.
	 *
	 * @var GridExtender
	 */
	private $gridExtender;

	/**
	 * Controller constructor.
	 *
	 * @param Modal            $orderModal       Modal.
	 * @param ControllerRouter $controllerRouter Router.
	 * @param PacketSubmitter  $packetSubmitter  Packet submitter.
	 * @param Order\Repository $orderRepository  Order repository.
	 * @param GridExtender     $gridExtender     Grid extender.
	 */
	public function __construct(
		Modal $orderModal,
		ControllerRouter $controllerRouter,
		PacketSubmitter $packetSubmitter,
		Order\Repository $orderRepository,
		GridExtender $gridExtender
	) {
		$this->orderModal      = $orderModal;
		$this->router          = $controllerRouter;
		$this->namespace       = $controllerRouter->getNamespace();
		$this->rest_base       = $controllerRouter->getRestBase();
		$this->packetSubmitter = $packetSubmitter;
		$this->orderRepository = $orderRepository;
		$this->gridExtender    = $gridExtender;
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
	 * @return WP_REST_Response
	 */
	public function submitToApi( WP_REST_Request $request ) {
		$data       = [];
		$parameters = $request->get_body_params();
		$orderId    = $parameters['orderId'];
		$wcOrder    = wc_get_order( $orderId );

		$resultsCounter = [
			'success' => 0,
			'ignored' => 0,
			'errors'  => 0,
			'logs'    => 0,
		];
		if ( false === $wcOrder ) {
			// translators: %s is order id.
			$resultsCounter['errors'] = sprintf( __( 'Order %s does not exist.', 'packeta' ), $orderId );
		} else {
			$this->packetSubmitter->submitPacket( $wcOrder, $resultsCounter );
		}
		$data['redirectTo'] = add_query_arg(
			[
				'post_type'          => 'shop_order',
				'packetery_order_id' => $orderId,
				'submit_to_api'      => '1',
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
	public function saveModal( WP_REST_Request $request ) {
		$data                    = [];
		$parameters              = $request->get_body_params();
		$packeteryWeight         = $parameters['packeteryWeight'];
		$packeteryOriginalWeight = $parameters['packeteryOriginalWeight'];
		$packeteryWidth          = $parameters['packeteryWidth'];
		$packeteryLength         = $parameters['packeteryLength'];
		$packeteryHeight         = $parameters['packeteryHeight'];
		$orderId                 = (int) $parameters['orderId'];

		$form = $this->orderModal->createForm();
		$form->setValues(
			[
				'packetery_weight'          => $packeteryWeight,
				'packetery_original_weight' => $packeteryOriginalWeight,
				'packetery_width'           => $packeteryWidth,
				'packetery_length'          => $packeteryLength,
				'packetery_height'          => $packeteryHeight,
			]
		);

		if ( false === $form->isValid() ) {
			return new WP_Error( 'form_invalid', implode( ', ', $form->getErrors() ), 400 );
		}

		$order = $this->orderRepository->getById( $orderId );
		if ( null === $order ) {
			return new WP_Error( 'order_not_loaded', __( 'Order could not be loaded.', 'packeta' ), 400 );
		}

		$values = $form->getValues( 'array' );
		if (
			is_numeric( $values['packetery_weight'] ) &&
			(float) $values['packetery_weight'] !== (float) $values['packetery_original_weight']
		) {
			$order->setWeight( (float) $values['packetery_weight'] );
		}

		if ( ! is_numeric( $values['packetery_weight'] ) ) {
			$order->setWeight( null );
		}

		foreach ( [ 'packetery_length', 'packetery_width', 'packetery_height' ] as $sizeKey ) {
			if ( ! is_numeric( $values[ $sizeKey ] ) ) {
				$values[ $sizeKey ] = null;
			}
		}

		$size = new Size(
			$values['packetery_length'],
			$values['packetery_width'],
			$values['packetery_height']
		);

		$order->setSize( $size );
		$this->orderRepository->save( $order );

		$data['message'] = __( 'Success', 'packeta' );
		$data['data']    = [
			'fragments'        => [
				sprintf( '[data-packetery-order-id="%d"][data-packetery-order-grid-cell-weight]', $orderId ) => $this->gridExtender->getWeightCellContent( $order ),
			],
			'packetery_weight' => $order->getFinalWeight(),
			'packetery_length' => $order->getLength(),
			'packetery_width'  => $order->getWidth(),
			'packetery_height' => $order->getHeight(),
			'showWarningIcon'  => $this->orderModal->showWarningIcon( $order ),
		];

		return new WP_REST_Response( $data, 200 );
	}
}
