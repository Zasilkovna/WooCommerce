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
use Packetery\Module\Api\Internal\ReturnController;

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
	 * Return controller.
	 *
	 * @var ReturnController
	 */
	private $returnController;

	/**
	 * Constructor.
	 *
	 * @param OrderController    $orderController Order controller.
	 * @param CheckoutController $checkoutController Checkout controller.
	 * @param ReturnController   $returnController Return controller.
	 */
	public function __construct(
		OrderController $orderController,
		CheckoutController $checkoutController,
		ReturnController $returnController
	) {
		$this->orderController    = $orderController;
		$this->checkoutController = $checkoutController;
		$this->returnController   = $returnController;
	}

	/**
	 * Register the routes of the controller.
	 *
	 * @return void
	 */
	public function registerRoutes(): void {
		$this->orderController->registerRoutes();
		$this->checkoutController->registerRoutes();
		$this->returnController->registerRoutes();
	}
}
