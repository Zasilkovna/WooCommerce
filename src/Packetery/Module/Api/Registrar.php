<?php
/**
 * Class Registrar
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Api;

use Packetery\Module\Api\Internal\CheckoutController;
use Packetery\Module\Api\Internal\OrderController;

/**
 * Class Registrar
 *
 * @package Packetery
 */
class Registrar {

	/**
	 * Order controller.
	 *
	 * @var OrderController
	 */
	private $orderController;

	/**
	 * Checkout controller.
	 *
	 * @var CheckoutController
	 */
	private $checkoutController;

	/**
	 * Constructor.
	 *
	 * @param OrderController    $orderController Order controller.
	 * @param CheckoutController $checkoutController Checkout controller.
	 */
	public function __construct(
		OrderController $orderController,
		CheckoutController $checkoutController
	) {
		$this->orderController    = $orderController;
		$this->checkoutController = $checkoutController;
	}

	/**
	 * Register the routes of the controller.
	 *
	 * @return void
	 */
	public function registerRoutes(): void {
		$this->orderController->registerRoutes();
		$this->checkoutController->registerRoutes();
	}

}
