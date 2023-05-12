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

	public const PATH_SAVE_MODAL = '/save-modal';

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

}
