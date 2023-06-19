<?php
/**
 * Class OrderController
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Api\Internal;

use Packetery\Core;
use Packetery\Core\Entity\Size;
use Packetery\Core\Validator;
use Packetery\Module\Exception\InvalidCarrierException;
use Packetery\Module\Order;
use Packetery\Module\Order\GridExtender;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Class OrderController
 *
 * @package Packetery
 */
final class OrderController extends WP_REST_Controller {

	/**
	 * Router.
	 *
	 * @var OrderRouter
	 */
	private $router;

	/**
	 * Order modal.
	 *
	 * @var Order\Modal
	 */
	private $orderModal;

	/**
	 * Order repository.
	 *
	 * @var Order\Repository
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
	 * @var Validator\Order
	 */
	private $orderValidator;

	/**
	 * Helper.
	 *
	 * @var Core\Helper
	 */
	private $helper;

	/**
	 * Controller constructor.
	 *
	 * @param OrderRouter      $router          Router.
	 * @param Order\Modal      $orderModal      Modal.
	 * @param Order\Repository $orderRepository Order repository.
	 * @param GridExtender     $gridExtender    Grid extender.
	 * @param Validator\Order  $orderValidator  Order validator.
	 * @param Core\Helper      $helper          Helper.
	 */
	public function __construct(
		OrderRouter $router,
		Order\Modal $orderModal,
		Order\Repository $orderRepository,
		GridExtender $gridExtender,
		Validator\Order $orderValidator,
		Core\Helper $helper
	) {
		$this->orderModal      = $orderModal;
		$this->orderRepository = $orderRepository;
		$this->gridExtender    = $gridExtender;
		$this->orderValidator  = $orderValidator;
		$this->helper          = $helper;
		$this->router          = $router;
	}

	/**
	 * Register the routes of the controller.
	 *
	 * @return void
	 */
	public function registerRoutes(): void {
		$this->router->registerRoute(
			OrderRouter::PATH_SAVE_MODAL,
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

		try {
			$order = $this->orderRepository->getById( $orderId );
		} catch ( InvalidCarrierException $exception ) {
			return new WP_Error( 'order_not_loaded', $exception->getMessage(), 400 );
		}
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
			'packetery_deliver_on' => $this->helper->getStringFromDateTime( $order->getDeliverOn(), Core\Helper::DATEPICKER_FORMAT ),
			'orderIsSubmittable'   => $this->orderValidator->isValid( $order ),
			'hasOrderManualWeight' => $order->hasManualWeight(),
		];

		return new WP_REST_Response( $data, 200 );
	}

}
