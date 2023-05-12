<?php
/**
 * Class CheckoutController
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Api\Internal;

use Packetery\Module\Checkout;
use Packetery\Module\Order\Attribute;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Class CheckoutController. Input is validated in Checkout. We use transient because WC session is not yet loaded.
 *
 * @package Packetery
 */
final class CheckoutController extends WP_REST_Controller {

	private const RATE_ID = 'packetery_rate_id';

	/**
	 * Router.
	 *
	 * @var CheckoutRouter
	 */
	private $router;

	/**
	 * Checkout.
	 *
	 * @var Checkout
	 */
	private $checkout;

	/**
	 * Constructor.
	 *
	 * @param CheckoutRouter $router   Router.
	 * @param Checkout       $checkout Router.
	 */
	public function __construct( CheckoutRouter $router, Checkout $checkout ) {
		$this->router   = $router;
		$this->checkout = $checkout;
	}

	/**
	 * Register the routes of the controller. Endpoints are public.
	 *
	 * @return void
	 */
	public function registerRoutes(): void {
		$this->router->registerRoute(
			CheckoutRouter::PATH_SAVE_SELECTED_PICKUP_POINT,
			[
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'saveSelectedPickupPoint' ],
					'permission_callback' => '__return_true',
				],
			]
		);
		$this->router->registerRoute(
			CheckoutRouter::PATH_SAVE_VALIDATED_ADDRESS,
			[
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'saveValidatedAddress' ],
					'permission_callback' => '__return_true',
				],
			]
		);
		$this->router->registerRoute(
			CheckoutRouter::PATH_REMOVE_SAVED_DATA,
			[
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'removeSavedData' ],
					'permission_callback' => '__return_true',
				],
			]
		);
	}

	/**
	 * Save selected pickup point.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function saveSelectedPickupPoint( WP_REST_Request $request ): WP_REST_Response {
		$parameters = $request->get_body_params();
		$savedData  = get_transient( $this->checkout->getTransientNamePacketaCheckoutData() );
		if ( ! is_array( $savedData ) ) {
			$savedData = [];
		}

		$rateId = $parameters[ self::RATE_ID ];
		foreach ( Attribute::$pickupPointAttrs as $attribute ) {
			$savedData[ $rateId ][ $attribute['name'] ] = $parameters[ $attribute['name'] ];
		}

		set_transient(
			$this->checkout->getTransientNamePacketaCheckoutData(),
			$savedData,
			DAY_IN_SECONDS
		);

		return new WP_REST_Response( [], 200 );
	}

	/**
	 * Save validated address.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function saveValidatedAddress( WP_REST_Request $request ): WP_REST_Response {
		$parameters = $request->get_body_params();
		$savedData  = get_transient( $this->checkout->getTransientNamePacketaCheckoutData() );
		if ( ! is_array( $savedData ) ) {
			$savedData = [];
		}

		$rateId = $parameters[ self::RATE_ID ];
		foreach ( Attribute::$homeDeliveryAttrs as $attribute ) {
			$savedData[ $rateId ][ $attribute['name'] ] = $parameters[ $attribute['name'] ];
		}

		set_transient(
			$this->checkout->getTransientNamePacketaCheckoutData(),
			$savedData,
			DAY_IN_SECONDS
		);

		return new WP_REST_Response( [], 200 );
	}

	/**
	 * Removes saved selected pickup point or validated address.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function removeSavedData( WP_REST_Request $request ): WP_REST_Response {
		delete_transient( $this->checkout->getTransientNamePacketaCheckoutData() );

		return new WP_REST_Response( [], 200 );
	}

}
