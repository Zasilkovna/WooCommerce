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
use Packetery\Core\Helper;
use Packetery\Core\Validator;
use Packetery\Module\Exception\InvalidCarrierException;
use Packetery\Module\Order;
use Packetery\Module\Order\Attribute;
use Packetery\Module\Order\Form;
use Packetery\Module\Order\Repository;
use Packetery\Module\Order\GridExtender;
use Packetery\Module\Order\AttributeMapper;
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
	 * @var Form
	 */
	private $orderForm;

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
	 * Order attribute mapper.
	 *
	 * @var AttributeMapper
	 */
	private $mapper;

	/**
	 * Controller constructor.
	 *
	 * @param OrderRouter     $router Router.
	 * @param Repository      $orderRepository Order repository.
	 * @param GridExtender    $gridExtender Grid extender.
	 * @param Validator\Order $orderValidator Order validator.
	 * @param Helper          $helper Helper.
	 * @param Form            $orderForm Order form.
	 * @param AttributeMapper $mapper Attribute mapper.
	 */
	public function __construct(
		OrderRouter $router,
		Repository $orderRepository,
		GridExtender $gridExtender,
		Validator\Order $orderValidator,
		Helper $helper,
		Form $orderForm,
		AttributeMapper $mapper
	) {
		$this->orderForm       = $orderForm;
		$this->orderRepository = $orderRepository;
		$this->gridExtender    = $gridExtender;
		$this->orderValidator  = $orderValidator;
		$this->helper          = $helper;
		$this->router          = $router;
		$this->mapper          = $mapper;
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
		$this->router->registerRoute(
			OrderRouter::PATH_SAVE_DELIVERY_ADDRESS,
			[
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'updateOrderDeliveryAddress' ],
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
		$data               = [];
		$parameters         = $request->get_body_params();
		$packeteryDeliverOn = $parameters['packeteryDeliverOn'] ?? null;
		$orderId            = (int) $parameters['orderId'];

		$form = $this->orderForm->create();
		$form->setValues(
			[
				Form::FIELD_WEIGHT          => $parameters['packeteryWeight'],
				Form::FIELD_ORIGINAL_WEIGHT => $parameters['packeteryOriginalWeight'],
				Form::FIELD_WIDTH           => $parameters['packeteryWidth'] ?? null,
				Form::FIELD_LENGTH          => $parameters['packeteryLength'] ?? null,
				Form::FIELD_HEIGHT          => $parameters['packeteryHeight'] ?? null,
				Form::FIELD_ADULT_CONTENT   => isset( $parameters['hasPacketeryAdultContent'] ) && 'true' === $parameters['hasPacketeryAdultContent'],
				Form::FIELD_COD             => $parameters['packeteryCOD'] ?? null,
				Form::FIELD_VALUE           => $parameters['packeteryValue'],
				Form::FIELD_DELIVER_ON      => $packeteryDeliverOn,
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

		$size = new Size(
			$values[ Form::FIELD_LENGTH ],
			$values[ Form::FIELD_WIDTH ],
			$values[ Form::FIELD_HEIGHT ]
		);

		if ( $values[ Form::FIELD_WEIGHT ] !== (float) $values[ Form::FIELD_ORIGINAL_WEIGHT ] ) {
			$order->setWeight( $values[ Form::FIELD_WEIGHT ] );
		}

		$order->setCod( $values[ Form::FIELD_COD ] );
		$order->setAdultContent( $values[ Form::FIELD_ADULT_CONTENT ] );
		$order->setValue( $values[ Form::FIELD_VALUE ] );
		$order->setSize( $size );
		// TODO: Find out why are we using this variable and not form value.
		$order->setDeliverOn( $this->helper->getDateTimeFromString( $packeteryDeliverOn ) );

		$this->orderRepository->save( $order );

		$data['message'] = __( 'Success', 'packeta' );
		$data['data']    = [
			'fragments'               => [
				sprintf( '[data-packetery-order-id="%d"][data-packetery-order-grid-cell-weight]', $orderId ) => $this->gridExtender->getWeightCellContent( $order ),
			],
			Form::FIELD_WEIGHT        => $order->getFinalWeight(),
			Form::FIELD_LENGTH        => $order->getLength(),
			Form::FIELD_WIDTH         => $order->getWidth(),
			Form::FIELD_HEIGHT        => $order->getHeight(),
			Form::FIELD_ADULT_CONTENT => $order->containsAdultContent(),
			Form::FIELD_COD           => $order->getCod(),
			Form::FIELD_VALUE         => $order->getValue(),
			Form::FIELD_DELIVER_ON    => $this->helper->getStringFromDateTime( $order->getDeliverOn(), Core\Helper::DATEPICKER_FORMAT ),
			'orderIsSubmittable'      => $this->orderValidator->isValid( $order ),
			'hasOrderManualWeight'    => $order->hasManualWeight(),
		];

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Update one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function updateOrderDeliveryAddress( WP_REST_Request $request ) {
		$data       = [];
		$parameters = $request->get_body_params();
		$orderId    = (int) $parameters['orderId'];

		try {
			$order = $this->orderRepository->getById( $orderId );
		} catch ( InvalidCarrierException $exception ) { // New exception needed?
			return new WP_Error( 'order_not_loaded', $exception->getMessage(), 400 );
		}
		if ( null === $order ) {
			return new WP_Error( 'order_not_loaded', __( 'Order could not be loaded.', 'packeta' ), 400 ); // Display an error message to the user.
		}

		// TODO: Validate the order is being updated by the user it belongs to only!
//		$wcOrder = $this->orderRepository->getWcOrderById( $orderId );
//		if (is_user_logged_in() && wp_get_current_user()->ID !== $wcOrder->get_user_id()) {
//			return new WP_Error('unauthorized', __('You shall not pass', 'packeta'), 401 );
//		}

		if ( $order->getPacketId() ) {
			$data['message'] = __( 'This action is no longer available. The packet has been submitted to Packeta.', 'packeta' );
			$data['type']    = 'error';

			return new WP_REST_Response( $data, 200 );
		}

		$delivery_address = [];
		foreach ( Attribute::$carDeliveryAttrs as $addressField ) {
			$fieldName = $addressField['name'];
			if ( isset( $addressField['isWidgetResultField'] ) ) {
				continue;
			}
			if ( isset( $parameters[ $fieldName ] ) ) {
				$delivery_address[ $fieldName ] = $parameters[ $fieldName ];
			}
		}

		$address = $this->mapper->toCarDeliveryAddress( $delivery_address );
		$order->setDeliveryAddress( $address );
		$order->setCarDeliveryId( $parameters['packetery_car_delivery_id'] );
		$this->orderRepository->save( $order );

		$data['message'] = __( 'The delivery address has been changed', 'packeta' );
		$data['type']    = 'success';

		return new WP_REST_Response( $data, 200 );
	}
}
