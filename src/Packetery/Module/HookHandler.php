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
	 * Metabox Wrapper.
	 *
	 * @var Order\MetaboxesWrapper
	 */
	private $metaboxesWrapper;

	/**
	 * Order repository.
	 *
	 * @var Order\Repository
	 */
	private $orderRepository;

	/**
	 * Constructor.
	 *
	 * @param Order\MetaboxesWrapper $metaboxesWrapper Metaboxes Wrapper.
	 * @param Order\Repository       $orderRepository  Order repository.
	 */
	public function __construct(
		Order\MetaboxesWrapper $metaboxesWrapper,
		Order\Repository $orderRepository
	) {
		$this->metaboxesWrapper = $metaboxesWrapper;
		$this->orderRepository  = $orderRepository;
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

		$wcOrder = $this->orderRepository->getWcOrderById( (int) $wcOrderId );
		if ( null === $wcOrder ) {
			return;
		}

		if ( ! $wcOrder->has_shipping_method( ShippingMethod::PACKETERY_METHOD_ID ) ) {
			$this->orderRepository->delete( (int) $wcOrderId );
			$hasBeenRun = true;

			return;
		}

		$this->metaboxesWrapper->saveFields( $wcOrderId );

		$hasBeenRun = true;
	}
}
