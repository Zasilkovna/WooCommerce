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
use Packetery\Module\Order\Repository;
use Packetery\Module\Order\OrderForm;
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
	 * @var OrderForm
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
	 * Controller constructor.
	 *
	 * @param OrderRouter     $router Router.
	 * @param Repository      $orderRepository Order repository.
	 * @param GridExtender    $gridExtender Grid extender.
	 * @param Validator\Order $orderValidator Order validator.
	 * @param Helper          $helper Helper.
	 * @param OrderForm       $orderForm Order form.
	 */
	public function __construct(
		OrderRouter $router,
		Repository $orderRepository,
		GridExtender $gridExtender,
		Validator\Order $orderValidator,
		Helper $helper,
		OrderForm $orderForm
	) {
		$this->orderForm       = $orderForm;
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
		$data               = [];
		$parameters         = $request->get_body_params();
		$packeteryDeliverOn = $parameters['packeteryDeliverOn'];
		$orderId            = (int) $parameters['orderId'];

		$form = $this->orderForm->create();
		$form->setValues(
			[
				OrderForm::FIELD_WEIGHT          => $parameters['packeteryWeight'],
				OrderForm::FIELD_ORIGINAL_WEIGHT => $parameters['packeteryOriginalWeight'],
				OrderForm::FIELD_WIDTH           => $parameters['packeteryWidth'] ?? null,
				OrderForm::FIELD_LENGTH          => $parameters['packeteryLength'] ?? null,
				OrderForm::FIELD_HEIGHT          => $parameters['packeteryHeight'] ?? null,
				OrderForm::FIELD_ADULT_CONTENT   => 'true' === $parameters['packeteryAdultContent'],
				OrderForm::FIELD_COD             => $parameters['packeteryCOD'] ?? null,
				OrderForm::FIELD_VALUE           => $parameters['packeteryValue'],
				OrderForm::FIELD_DELIVER_ON      => $packeteryDeliverOn,
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
			$values[ OrderForm::FIELD_LENGTH ],
			$values[ OrderForm::FIELD_WIDTH ],
			$values[ OrderForm::FIELD_HEIGHT ]
		);

		if ( (float) $values[ OrderForm::FIELD_WEIGHT ] !== (float) $values['packetery_original_weight'] ) {
			$order->setWeight( (float) $values[ OrderForm::FIELD_WEIGHT ] );
		}

		$order->setCod( $values[ OrderForm::FIELD_COD ] );
		$order->setAdultContent( $values[ OrderForm::FIELD_ADULT_CONTENT ] );
		$order->setValue( $values[ OrderForm::FIELD_VALUE ] );
		$order->setSize( $size );
		// TODO: Find out why are we using this variable and not form value.
		$order->setDeliverOn( $this->helper->getDateTimeFromString( $packeteryDeliverOn ) );

		$this->orderRepository->save( $order );

		$data['message'] = __( 'Success', 'packeta' );
		$data['data']    = [
			'fragments'                    => [
				sprintf( '[data-packetery-order-id="%d"][data-packetery-order-grid-cell-weight]', $orderId ) => $this->gridExtender->getWeightCellContent( $order ),
			],
			OrderForm::FIELD_WEIGHT        => $order->getFinalWeight(),
			OrderForm::FIELD_LENGTH        => $order->getLength(),
			OrderForm::FIELD_WIDTH         => $order->getWidth(),
			OrderForm::FIELD_HEIGHT        => $order->getHeight(),
			OrderForm::FIELD_ADULT_CONTENT => $order->containsAdultContent(),
			OrderForm::FIELD_COD           => $order->getCod(),
			OrderForm::FIELD_VALUE         => $order->getValue(),
			OrderForm::FIELD_DELIVER_ON    => $this->helper->getStringFromDateTime( $order->getDeliverOn(), Core\Helper::DATEPICKER_FORMAT ),
			'orderIsSubmittable'           => $this->orderValidator->isValid( $order ),
			'hasOrderManualWeight'         => $order->hasManualWeight(),
		];

		return new WP_REST_Response( $data, 200 );
	}

}
