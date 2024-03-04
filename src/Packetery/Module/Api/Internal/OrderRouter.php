<?php
/**
 * Class OrderRouter
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Api\Internal;

use Packetery\Module\Api\BaseRouter;

/**
 * Class OrderRouter
 *
 * @package Packetery
 */
final class OrderRouter extends BaseRouter {

	public const PATH_SAVE_MODAL            = '/save-modal';
	public const PATH_SAVE_DELIVERY_ADDRESS = '/save-delivery-address';

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
	protected $restBase = 'order';

	/**
	 * Gets endpoint URL.
	 *
	 * @return string
	 */
	public function getSaveModalUrl(): string {
		return $this->getRouteUrl( self::PATH_SAVE_MODAL );
	}

	/**
	 * Gets endpoint URL.
	 *
	 * @return string
	 */
	public function getSaveDeliveryAddressUrl(): string {
		return $this->getRouteUrl( self::PATH_SAVE_DELIVERY_ADDRESS );
	}

}
