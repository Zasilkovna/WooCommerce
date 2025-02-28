<?php
/**
 * Class CheckoutController
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Api\Internal;

use Packetery\Module\Checkout\CheckoutStorage;
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
	 * @var CheckoutRouter
	 */
	private $router;

	/**
	 * @var CheckoutStorage
	 */
	private $checkoutStorage;

	public function __construct( CheckoutRouter $router, CheckoutStorage $checkoutStorage ) {
		$this->router          = $router;
		$this->checkoutStorage = $checkoutStorage;
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
		$this->router->registerRoute(
			CheckoutRouter::PATH_SAVE_DELIVERY_ADDRESS,
			[
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'saveCarDeliveryDetails' ],
					'permission_callback' => '__return_true',
				],
			]
		);
	}

	/**
	 * @param WP_REST_Request<string[]> $request
	 *
	 * @return WP_REST_Response
	 */
	// phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint
	public function saveSelectedPickupPoint( WP_REST_Request $request ): WP_REST_Response {
		$this->save( $request, Attribute::$pickupPointAttributes );

		return new WP_REST_Response( [], 200 );
	}

	/**
	 * @param WP_REST_Request<string[]> $request
	 *
	 * @return WP_REST_Response
	 */
	// phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint
	public function saveValidatedAddress( WP_REST_Request $request ): WP_REST_Response {
		$this->save( $request, Attribute::$homeDeliveryAttributes );

		return new WP_REST_Response( [], 200 );
	}

	/**
	 * @param WP_REST_Request<string[]> $request
	 *
	 * @return WP_REST_Response
	 */
	// phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint
	public function saveCarDeliveryDetails( WP_REST_Request $request ): WP_REST_Response {
		$this->save( $request, Attribute::$carDeliveryAttributes );

		return new WP_REST_Response( [], 200 );
	}

	/**
	 * Removes saved selected pickup point or validated address.
	 *
	 * @param WP_REST_Request<string[]> $request
	 *
	 * @return WP_REST_Response
	 */
	// phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint
	public function removeSavedData( WP_REST_Request $request ): WP_REST_Response {
		$params = $request->get_params();
		if ( ! isset( $params['carrierId'] ) || $params['carrierId'] === '' ) {
			$this->checkoutStorage->deleteTransient();
		} else {
			$savedData = $this->checkoutStorage->getFromTransient();
			// False when does not exist, empty string when improperly saved.
			if ( ! is_array( $savedData ) ) {
				if ( $savedData === '' ) {
					$this->checkoutStorage->deleteTransient();
				}

				return new WP_REST_Response( [], 200 );
			}

			unset( $savedData[ $params['carrierId'] ] );

			$this->checkoutStorage->setTransient( $savedData );
		}

		return new WP_REST_Response( [], 200 );
	}

	/**
	 * Saves carrier data.
	 *
	 * @param WP_REST_Request<string[]> $request
	 * @param array                          $carrierAttrs
	 *
	 * @return void
	 */
	// phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint
	private function save( WP_REST_Request $request, array $carrierAttrs ): void {
		$parameters = $request->get_body_params();
		$savedData  = $this->checkoutStorage->getFromTransient();
		if ( ! is_array( $savedData ) ) {
			$savedData = [];
		}

		$rateId = $parameters[ self::RATE_ID ];
		foreach ( $carrierAttrs as $attribute ) {
			$savedData[ $rateId ][ $attribute['name'] ] = $parameters[ $attribute['name'] ];
		}

		$this->checkoutStorage->setTransient( $savedData );
	}
}
