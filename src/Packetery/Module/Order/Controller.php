<?php
/**
 * Class Controller
 *
 * @package Packetery\Module\Order
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Entity\Size;
use Packetery\Core\Helper;
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
	 * Router.
	 *
	 * @var ControllerRouter
	 */
	private $router;

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
	 * Order validator.
	 *
	 * @var \Packetery\Core\Validator\Order
	 */
	private $orderValidator;

	/**
	 * Helper.
	 *
	 * @var \Packetery\Core\Helper
	 */
	private $helper;

	/**
	 * Controller constructor.
	 *
	 * @param Modal                           $orderModal       Modal.
	 * @param ControllerRouter                $controllerRouter Router.
	 * @param Order\Repository                $orderRepository  Order repository.
	 * @param GridExtender                    $gridExtender     Grid extender.
	 * @param \Packetery\Core\Validator\Order $orderValidator   Order validator.
	 * @param \Packetery\Core\Helper          $helper           Helper.
	 */
	public function __construct(
		Modal $orderModal,
		ControllerRouter $controllerRouter,
		Order\Repository $orderRepository,
		GridExtender $gridExtender,
		\Packetery\Core\Validator\Order $orderValidator,
		Helper $helper
	) {
		$this->orderModal      = $orderModal;
		$this->router          = $controllerRouter;
		$this->namespace       = $controllerRouter->getNamespace();
		$this->rest_base       = $controllerRouter->getRestBase();
		$this->orderRepository = $orderRepository;
		$this->gridExtender    = $gridExtender;
		$this->orderValidator  = $orderValidator;
		$this->helper          = $helper;
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
		$packeteryDeliverOn      = $parameters['packeteryDeliverOn'];
		$orderId                 = (int) $parameters['orderId'];

		$form = $this->orderModal->createForm();
		$form->setValues(
			[
				'packetery_weight'          => $packeteryWeight,
				'packetery_original_weight' => $packeteryOriginalWeight,
				'packetery_width'           => $packeteryWidth,
				'packetery_length'          => $packeteryLength,
				'packetery_height'          => $packeteryHeight,
				'packetery_deliver_on'      => $packeteryDeliverOn,
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

		if ( ! is_numeric( $values['packetery_weight'] ) ) {
			$order->setWeight( null );
		} elseif ( (float) $values['packetery_weight'] !== (float) $values['packetery_original_weight'] ) {
			$order->setWeight( (float) $values['packetery_weight'] );
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
		$order->setDeliverOn( $this->helper->getDateTimeFromString( $packeteryDeliverOn ) );
		$this->orderRepository->save( $order );

		$data['message'] = __( 'Success', 'packeta' );
		$data['data']    = [
			'fragments'            => [
				sprintf( '[data-packetery-order-id="%d"][data-packetery-order-grid-cell-weight]', $orderId ) => $this->gridExtender->getWeightCellContent( $order ),
			],
			'packetery_weight'     => $order->getFinalWeight(),
			'packetery_length'     => $order->getLength(),
			'packetery_width'      => $order->getWidth(),
			'packetery_height'     => $order->getHeight(),
			'packetery_deliver_on' => $this->helper->getStringFromDateTime( $order->getDeliverOn(), Helper::DATEPICKER_FORMAT ),
			'orderIsSubmittable'   => $this->orderValidator->validate( $order ),
			'hasOrderManualWeight' => $order->hasManualWeight(),
		];

		return new WP_REST_Response( $data, 200 );
	}
}
