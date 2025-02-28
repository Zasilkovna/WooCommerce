<?php
/**
 * Class OrderController
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Api\Internal;

use Packetery\Core\CoreHelper;
use Packetery\Core\Entity\Size;
use Packetery\Core\Validator;
use Packetery\Module\Exception\InvalidCarrierException;
use Packetery\Module\Forms\FormData\OrderFormData;
use Packetery\Module\Forms\StoredUntilFormFactory;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Order;
use Packetery\Module\Order\Form;
use Packetery\Module\Order\GridExtender;
use Packetery\Module\Order\OrderValidatorFactory;
use Packetery\Module\Order\PacketSetStoredUntil;
use Packetery\Module\Order\Repository;
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
	 * Stored until Form Factory
	 *
	 * @var StoredUntilFormFactory
	 */
	private $storedUntilFormFactory;

	/**
	 * Packet Set Stored Until
	 *
	 * @var PacketSetStoredUntil
	 */
	private $packetSetStoredUntil;

	/**
	 * Options provider.
	 *
	 * @var OptionsProvider
	 */
	private $optionsProvider;

	/**
	 * Controller constructor.
	 *
	 * @param OrderRouter            $router                 Router.
	 * @param Repository             $orderRepository        Order repository.
	 * @param GridExtender           $gridExtender           Grid extender.
	 * @param OrderValidatorFactory  $orderValidatorFactory  Order validator.
	 * @param CoreHelper             $coreHelper             CoreHelper.
	 * @param Form                   $orderForm              Order form.
	 * @param StoredUntilFormFactory $storedUntilFormFactory Stored until Form Factory.
	 * @param PacketSetStoredUntil   $packetSetStoredUntil   Packet Set Stored Until.
	 * @param OptionsProvider        $optionsProvider        Options provider.
	 */
	public function __construct(
		OrderRouter $router,
		Repository $orderRepository,
		GridExtender $gridExtender,
		OrderValidatorFactory $orderValidatorFactory,
		CoreHelper $coreHelper,
		Form $orderForm,
		StoredUntilFormFactory $storedUntilFormFactory,
		PacketSetStoredUntil $packetSetStoredUntil,
		OptionsProvider $optionsProvider
	) {
		$this->orderForm              = $orderForm;
		$this->orderRepository        = $orderRepository;
		$this->gridExtender           = $gridExtender;
		$this->orderValidator         = $orderValidatorFactory->create();
		$this->coreHelper             = $coreHelper;
		$this->router                 = $router;
		$this->storedUntilFormFactory = $storedUntilFormFactory;
		$this->packetSetStoredUntil   = $packetSetStoredUntil;
		$this->optionsProvider        = $optionsProvider;
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
			OrderRouter::PATH_SAVE_STORED_UNTIL,
			[
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'saveStoredUntil' ],
					'permission_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
				],
			]
		);
	}

	/**
	 * @param WP_REST_Request<string[]> $request
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	// phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint
	public function saveModal( WP_REST_Request $request ) {
		$data               = [];
		$parameters         = $request->get_body_params();
		$packeteryDeliverOn = $parameters['packeteryDeliverOn'] ?? null;
		$orderId            = (int) $parameters['orderId'];

		$form = $this->orderForm->create();
		$form->setValues(
			[
				Form::FIELD_WEIGHT           => $parameters['packeteryWeight'],
				Form::FIELD_ORIGINAL_WEIGHT  => $parameters['packeteryOriginalWeight'],
				Form::FIELD_WIDTH            => $parameters['packeteryWidth'] ?? null,
				Form::FIELD_LENGTH           => $parameters['packeteryLength'] ?? null,
				Form::FIELD_HEIGHT           => $parameters['packeteryHeight'] ?? null,
				Form::FIELD_ADULT_CONTENT    => isset( $parameters['hasPacketeryAdultContent'] ) && $parameters['hasPacketeryAdultContent'] === 'true',
				Form::FIELD_COD              => $parameters['packeteryCOD'] ?? null,
				Form::FIELD_CALCULATED_COD   => $parameters['packeteryCalculatedCod'] ?? null,
				Form::FIELD_VALUE            => $parameters['packeteryValue'] ?? null,
				Form::FIELD_CALCULATED_VALUE => $parameters['packeteryCalculatedValue'] ?? null,
				Form::FIELD_DELIVER_ON       => $packeteryDeliverOn,
			]
		);

		if ( $form->isValid() === false ) {
			return new WP_Error( 'form_invalid', implode( ', ', $form->getErrors() ), 400 );
		}

		try {
			$order = $this->orderRepository->getById( $orderId );
		} catch ( InvalidCarrierException $exception ) {
			return new WP_Error( 'order_not_loaded', $exception->getMessage(), 400 );
		}
		if ( $order === null ) {
			return new WP_Error( 'order_not_loaded', __( 'Order could not be loaded.', 'packeta' ), 400 );
		}
		/** @var OrderFormData $orderFormData */
		$orderFormData = $form->getValues( OrderFormData::class );

		$size = new Size(
			$this->optionsProvider->getSanitizedDimensionValueInMm( $orderFormData->packeteryLength ),
			$this->optionsProvider->getSanitizedDimensionValueInMm( $orderFormData->packeteryWidth ),
			$this->optionsProvider->getSanitizedDimensionValueInMm( $orderFormData->packeteryHeight )
		);

		if ( $orderFormData->packeteryWeight !== (float) $orderFormData->packeteryOriginalWeight ) {
			$order->setWeight( $orderFormData->packeteryWeight );
		}

		if ( $orderFormData->packeteryCOD !== (float) $orderFormData->packeteryCalculatedCod ) {
			$order->setManualCod( $orderFormData->packeteryCOD );
		} else {
			$order->setManualCod( null );
		}
		$order->setAdultContent( $orderFormData->packeteryAdultContent );
		if ( $orderFormData->packeteryValue !== (float) $orderFormData->packeteryCalculatedValue ) {
			$order->setManualValue( $orderFormData->packeteryValue );
		} else {
			$order->setManualValue( null );
		}
		$order->setSize( $size );
		$order->setDeliverOn( $this->coreHelper->getDateTimeFromString( $packeteryDeliverOn ) );

		$this->orderRepository->save( $order );

		$data['message'] = __( 'Success', 'packeta' );
		$data['data']    = [
			'fragments'                  => [
				sprintf( '[data-packetery-order-id="%d"][data-packetery-order-grid-cell-weight]', $orderId ) => $this->gridExtender->getWeightCellContent( $order ),
			],
			Form::FIELD_WEIGHT           => $order->getFinalWeight(),
			Form::FIELD_ORIGINAL_WEIGHT  => $order->getCalculatedWeight(),
			Form::FIELD_LENGTH           => CoreHelper::trimDecimalPlaces( $orderFormData->packeteryLength, $this->optionsProvider->getDimensionsNumberOfDecimals() ),
			Form::FIELD_WIDTH            => CoreHelper::trimDecimalPlaces( $orderFormData->packeteryWidth, $this->optionsProvider->getDimensionsNumberOfDecimals() ),
			Form::FIELD_HEIGHT           => CoreHelper::trimDecimalPlaces( $orderFormData->packeteryHeight, $this->optionsProvider->getDimensionsNumberOfDecimals() ),
			Form::FIELD_ADULT_CONTENT    => $order->containsAdultContent(),
			Form::FIELD_COD              => $order->getFinalCod(),
			Form::FIELD_CALCULATED_COD   => $order->getCalculatedCod(),
			Form::FIELD_VALUE            => $order->getFinalValue(),
			Form::FIELD_CALCULATED_VALUE => $order->getCalculatedValue(),
			Form::FIELD_DELIVER_ON       => $this->coreHelper->getStringFromDateTime( $order->getDeliverOn(), CoreHelper::DATEPICKER_FORMAT ),
			'orderIsSubmittable'         => $this->orderValidator->isValid( $order ),
			'orderWarningFields'         => Form::getInvalidFieldsFromValidationResult( $this->orderValidator->validate( $order ) ),
			'hasOrderManualWeight'       => $order->hasManualWeight(),
			'hasOrderManualCod'          => $order->hasManualCod(),
			'hasOrderManualValue'        => $order->hasManualValue(),
		];

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * @param WP_REST_Request<string[]> $request
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	// phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint
	public function saveStoredUntil( WP_REST_Request $request ) {
		$data        = [];
		$parameters  = $request->get_body_params();
		$orderId     = (int) $parameters['orderId'];
		$storedUntil = $parameters['packeteryStoredUntil'] ?? null;

		$form = $this->storedUntilFormFactory->createForm( sprintf( '%s_form', Order\StoredUntilModal::MODAL_ID ) );
		$form->setValues(
			[
				'packetery_stored_until' => $storedUntil,
			]
		);

		if ( $form->isValid() === false ) {
			return new WP_Error( 'form_invalid', implode( ', ', $form->getErrors() ), 400 );
		}

		try {
			$order = $this->orderRepository->getById( $orderId );
		} catch ( InvalidCarrierException $exception ) {
			return new WP_Error( 'order_not_loaded', $exception->getMessage(), 400 );
		}
		if ( $order === null ) {
			return new WP_Error( 'order_not_loaded', __( 'Order could not be loaded.', 'packeta' ), 400 );
		}

		$errorMessage = $this->packetSetStoredUntil->setStoredUntil( $order, $order->getPacketId(), $this->coreHelper->getDateTimeFromString( $storedUntil ) );

		if ( $errorMessage !== null ) {
			return new WP_Error( 'packetery_fault', $errorMessage, 400 );
		}

		$order->setStoredUntil( $this->coreHelper->getDateTimeFromString( $storedUntil ) );

		$this->orderRepository->save( $order );

		$data['message'] = __( 'Success', 'packeta' );
		$data['data']    = [
			'packeteryStoredUntil' => $order->getStoredUntil(),
			'orderIsSubmittable'   => $this->orderValidator->isValid( $order ),
			'orderWarningFields'   => Form::getInvalidFieldsFromValidationResult( $this->orderValidator->validate( $order ) ),
		];

		return new WP_REST_Response( $data, 200 );
	}
}
