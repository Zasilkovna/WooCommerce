<?php
/**
 * Class ReturnController
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Api\Internal;

use Packetery\Core\CoreHelper;
use Packetery\Core\Entity\PacketReturn;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Order\Repository as OrderRepository;
use Packetery\Module\Returns\ReturnEligibilityService;
use Packetery\Module\Returns\ReturnService;
use WC_Order;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Handles the customer-initiated return submission from the front-office (My Account order detail).
 */
final class ReturnController extends WP_REST_Controller {

	private ReturnRouter $router;
	private OrderRepository $orderRepository;
	private ReturnEligibilityService $eligibilityService;
	private ReturnService $returnService;
	private OptionsProvider $optionsProvider;
	private CoreHelper $coreHelper;
	private WpAdapter $wpAdapter;

	public function __construct(
		ReturnRouter $router,
		OrderRepository $orderRepository,
		ReturnEligibilityService $eligibilityService,
		ReturnService $returnService,
		OptionsProvider $optionsProvider,
		CoreHelper $coreHelper,
		WpAdapter $wpAdapter
	) {
		$this->router             = $router;
		$this->orderRepository    = $orderRepository;
		$this->eligibilityService = $eligibilityService;
		$this->returnService      = $returnService;
		$this->optionsProvider    = $optionsProvider;
		$this->coreHelper         = $coreHelper;
		$this->wpAdapter          = $wpAdapter;
	}

	public function registerRoutes(): void {
		$this->router->registerRoute(
			ReturnRouter::PATH_CREATE,
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'createReturn' ],
					'permission_callback' => [ $this->wpAdapter, 'isUserLoggedIn' ],
				],
			]
		);
		$this->router->registerRoute(
			ReturnRouter::PATH_CREATE_GUEST,
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'createGuestReturn' ],
					'permission_callback' => '__return_true',
				],
			]
		);
	}

	/**
	 * Creates a return for a Packeta order owned by the current customer.
	 *
	 * @param WP_REST_Request<string[]> $request Request.
	 */
	// phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint
	public function createReturn( WP_REST_Request $request ): WP_REST_Response {
		$orderId = (int) $request->get_param( 'orderId' );
		$wcOrder = $this->orderRepository->getWcOrderById( $orderId );
		if ( $wcOrder === null || $wcOrder->get_customer_id() !== $this->wpAdapter->getCurrentUserId() ) {
			return $this->errorResponse( $this->wpAdapter->__( 'Order not found.', 'packeta' ), 404 );
		}

		return $this->createForOrder( $wcOrder, $request );
	}

	/**
	 * Creates a return for a guest, verified by order number and matching billing e-mail.
	 *
	 * @param WP_REST_Request<string[]> $request Request.
	 */
	// phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint
	public function createGuestReturn( WP_REST_Request $request ): WP_REST_Response {
		if ( ! $this->optionsProvider->isGuestReturnsAllowed() ) {
			return $this->errorResponse( $this->wpAdapter->__( 'Order not found.', 'packeta' ), 404 );
		}

		$orderNumber = (int) $request->get_param( 'orderNumber' );
		$email       = $this->wpAdapter->sanitizeEmail( (string) $request->get_param( 'email' ) );
		$wcOrder     = $this->orderRepository->getWcOrderById( $orderNumber );
		// Generic response for both a missing order and an e-mail mismatch to prevent order enumeration.
		if (
			$wcOrder === null
			|| $email === ''
			|| strtolower( $wcOrder->get_billing_email() ) !== strtolower( $email )
		) {
			return $this->errorResponse( $this->wpAdapter->__( 'Order not found.', 'packeta' ), 404 );
		}

		return $this->createForOrder( $wcOrder, $request );
	}

	/**
	 * Runs the eligibility gate and creates the return for an already-authorized order.
	 *
	 * @param WP_REST_Request<string[]> $request Request.
	 */
	// phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint
	private function createForOrder( WC_Order $wcOrder, WP_REST_Request $request ): WP_REST_Response {
		$order = $this->orderRepository->getByWcOrderWithValidCarrier( $wcOrder );
		if ( $order === null || ! $this->eligibilityService->isReturnableByCustomer( $order, $wcOrder ) ) {
			return $this->errorResponse( $this->wpAdapter->__( 'This order cannot be returned.', 'packeta' ), 403 );
		}

		$email = $this->wpAdapter->sanitizeEmail( (string) $request->get_param( 'email' ) );
		if ( $email !== '' && $this->wpAdapter->isEmail( $email ) !== false ) {
			$order->setEmail( $email );
		}
		$phone = $this->wpAdapter->sanitizeTextField( (string) $request->get_param( 'phone' ) );
		if ( $phone !== '' ) {
			$order->setPhone( $phone );
		}

		$result = $this->returnService->createReturn( $order, PacketReturn::SOURCE_CUSTOMER );
		if ( $result->hasFault() ) {
			return $this->errorResponse(
				$this->wpAdapter->__( 'The return could not be created. Please try again later.', 'packeta' ),
				500
			);
		}

		$claimId = $result->getResponse()->getId();

		return new WP_REST_Response(
			[
				'barcode'     => 'Z' . $claimId,
				'trackingUrl' => $this->coreHelper->getTrackingUrl( $claimId ),
			],
			200
		);
	}

	private function errorResponse( string $message, int $status ): WP_REST_Response {
		return new WP_REST_Response( [ 'message' => $message ], $status );
	}
}
