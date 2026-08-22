<?php
/**
 * Class ReturnRouter
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Api\Internal;

use Packetery\Module\Api\BaseRouter;

/**
 * Class ReturnRouter
 *
 * @package Packetery
 */
class ReturnRouter extends BaseRouter {

	public const PATH_CREATE       = '/create';
	public const PATH_CREATE_GUEST = '/create-guest';

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
	protected $restBase = 'return';

	/**
	 * Gets endpoint URL.
	 */
	public function getCreateUrl(): string {
		return $this->getRouteUrl( self::PATH_CREATE );
	}

	/**
	 * Gets endpoint URL.
	 */
	public function getCreateGuestUrl(): string {
		return $this->getRouteUrl( self::PATH_CREATE_GUEST );
	}
}
