<?php
/**
 * Class HookHandler.
 *
 * @package Packetery
 */

declare( strict_types=1 );


namespace Packetery\Module;

/**
 * Class HookHandler.
 */
class HookHandler {

	/**
	 * Order repository.
	 *
	 * @var Order\Repository
	 */
	private $orderRepository;

	/**
	 * Customs Declaration repository.
	 *
	 * @var CustomsDeclaration\Repository
	 */
	private $customsDeclarationRepository;

	/**
	 * Constructor.
	 *
	 * @param Order\Repository              $orderRepository Order repository.
	 * @param CustomsDeclaration\Repository $customsDeclarationRepository Customs Declaration repository.
	 */
	public function __construct(
		Order\Repository $orderRepository,
		CustomsDeclaration\Repository $customsDeclarationRepository
	) {
		$this->orderRepository              = $orderRepository;
		$this->customsDeclarationRepository = $customsDeclarationRepository;
	}

	/**
	 * Registers hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'woocommerce_update_order', [ $this, 'updateOrder' ] );
	}

	/**
	 * Runs multiple scripts when the WC function is triggered.
	 *
	 * @param int|mixed $wcOrderId Order ID.
	 *
	 * @return void
	 */
	public function updateOrder( $wcOrderId ): void {
		static $hasBeenRun;

		if ( isset( $hasBeenRun ) && true === $hasBeenRun ) {
			return;
		}

		$hasBeenRun = true;

		$wcOrder = $this->orderRepository->getWcOrderById( (int) $wcOrderId );
		if ( null === $wcOrder ) {
			return;
		}

		if ( ! $wcOrder->has_shipping_method( ShippingMethod::PACKETERY_METHOD_ID ) ) {
			$this->customsDeclarationRepository->delete( (string) $wcOrderId );
			$this->orderRepository->delete( (int) $wcOrderId );
		}
	}
}
