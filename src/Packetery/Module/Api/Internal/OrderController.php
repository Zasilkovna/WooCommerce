<?php
/**
 * Class OrderController
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Api\Internal;

use Packetery\Core\Entity\Size;
use Packetery\Core\CoreHelper;
use Packetery\Core\Validator;
use Packetery\Module\Exception\InvalidCarrierException;
use Packetery\Module\Order;
use Packetery\Module\Order\Form;
use Packetery\Module\Order\Repository;
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
	 * CoreHelper.
	 *
	 * @var CoreHelper
	 */
	private $coreHelper;

	/**
	 * Controller constructor.
	 *
	 * @param OrderRouter     $router          Router.
	 * @param Repository      $orderRepository Order repository.
	 * @param GridExtender    $gridExtender    Grid extender.
	 * @param Validator\Order $orderValidator  Order validator.
	 * @param CoreHelper      $coreHelper      CoreHelper.
	 * @param Form            $orderForm       Order form.
	 */
	public function __construct(
		OrderRouter $router,
		Repository $orderRepository,
		GridExtender $gridExtender,
		Validator\Order $orderValidator,
		CoreHelper $coreHelper,
		Form $orderForm
	) {
		$this->orderForm       = $orderForm;
		$this->orderRepository = $orderRepository;
		$this->gridExtender    = $gridExtender;
		$this->orderValidator  = $orderValidator;
		$this->coreHelper      = $coreHelper;
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
		$order->setDeliverOn( $this->coreHelper->getDateTimeFromString( $packeteryDeliverOn ) );

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
			Form::FIELD_DELIVER_ON    => $this->coreHelper->getStringFromDateTime( $order->getDeliverOn(), CoreHelper::DATEPICKER_FORMAT ),
			'orderIsSubmittable'      => $this->orderValidator->isValid( $order ),
			'orderWarningFields'      => Form::getInvalidFieldsFromValidationResult( $this->orderValidator->validate( $order ) ),
			'hasOrderManualWeight'    => $order->hasManualWeight(),
		];

		return new WP_REST_Response( $data, 200 );
	}

}
