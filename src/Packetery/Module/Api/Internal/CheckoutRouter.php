<?php
/**
 * Class CheckoutRouter
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Api\Internal;

use Packetery\Module\Api\BaseRouter;

/**
 * Class CheckoutRouter
 *
 * @package Packetery
 */
class CheckoutRouter extends BaseRouter {

	public const PATH_SAVE_SELECTED_PICKUP_POINT = '/save-selected-pickup-point';
	public const PATH_SAVE_VALIDATED_ADDRESS     = '/save-validated-address';
	public const PATH_SAVE_DELIVERY_ADDRESS      = '/save-car-delivery-details';
	public const PATH_REMOVE_SAVED_DATA          = '/remove-saved-data';

	/**
	 * Namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'packeta/internal';

	/**
	 * Rest base.
	 *
	 * @var string
	 */
	protected $restBase = 'checkout';

	/**
	 * Gets endpoint URL.
	 *
	 * @return string
	 */
	public function getSaveSelectedPickupPointUrl(): string {
		return $this->getRouteUrl( self::PATH_SAVE_SELECTED_PICKUP_POINT );
	}

	/**
	 * Gets endpoint URL.
	 *
	 * @return string
	 */
	public function getSaveValidatedAddressUrl(): string {
		return $this->getRouteUrl( self::PATH_SAVE_VALIDATED_ADDRESS );
	}

	/**
	 * Gets endpoint URL.
	 *
	 * @return string
	 */
	public function getSaveCarDeliveryDetailsUrl(): string {
		return $this->getRouteUrl( self::PATH_SAVE_DELIVERY_ADDRESS );
	}

	/**
	 * Gets endpoint URL.
	 *
	 * @return string
	 */
	public function getRemoveSavedDataUrl(): string {
		return $this->getRouteUrl( self::PATH_REMOVE_SAVED_DATA );
	}

}
