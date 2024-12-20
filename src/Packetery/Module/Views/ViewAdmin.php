<?php

declare( strict_types=1 );

namespace Packetery\Module\Views;

use Packetery\Latte\Engine;
use Packetery\Module\Carrier\CarrierOptionsFactory;
use Packetery\Module\ContextResolver;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\ModuleHelper;
use Packetery\Module\Order;
use WC_Order;

use function __;

class ViewAdmin {

	/**
	 * @var ContextResolver
	 */
	private $contextResolver;

	/**
	 * @var Engine
	 */
	private $latteEngine;

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * @var Order\Repository
	 */
	private $orderRepository;

	/**
	 * @var CarrierOptionsFactory
	 */
	private $carrierOptionsFactory;

	/**
	 * @var ModuleHelper
	 */
	private $moduleHelper;

	public function __construct(
		ContextResolver $contextResolver,
		Engine $latteEngine,
		WpAdapter $wpAdapter,
		Order\Repository $orderRepository,
		CarrierOptionsFactory $carrierOptionsFactory,
		ModuleHelper $moduleHelper
	) {

		$this->contextResolver       = $contextResolver;
		$this->latteEngine           = $latteEngine;
		$this->wpAdapter             = $wpAdapter;
		$this->orderRepository       = $orderRepository;
		$this->carrierOptionsFactory = $carrierOptionsFactory;
		$this->moduleHelper          = $moduleHelper;
	}

	/**
	 * Renders delivery detail for packetery orders.
	 *
	 * @param WC_Order $wcOrder WordPress order.
	 */
	public function renderDeliveryDetail( WC_Order $wcOrder ): void {
		$order = $this->orderRepository->getByWcOrderWithValidCarrier( $wcOrder );
		if ( $order === null ) {
			return;
		}

		$carrierId      = $order->getCarrier()->getId();
		$carrierOptions = $this->carrierOptionsFactory->createByCarrierId( $carrierId );

		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/order/delivery-detail.latte',
			[
				'pickupPoint'              => $order->getPickupPoint(),
				'validatedDeliveryAddress' => $order->getValidatedDeliveryAddress(),
				'carrierAddressValidation' => $carrierOptions->getAddressValidation(),
				'isExternalCarrier'        => $order->isExternalCarrier(),
				'translations'             => [
					'packeta'                => $this->wpAdapter->__( 'Packeta', 'packeta' ),
					'pickupPointDetail'      => $this->wpAdapter->__( 'Pickup Point Detail', 'packeta' ),
					'name'                   => $this->wpAdapter->__( 'Name', 'packeta' ),
					'address'                => $this->wpAdapter->__( 'Address', 'packeta' ),
					'pickupPointDetailCaps'  => $this->wpAdapter->__( 'Pickup Point Detail', 'packeta' ),
					'addressWasNotValidated' => $this->wpAdapter->__( 'Address was not validated', 'packeta' ),
				],
			]
		);
	}

	/**
	 * Renders confirm modal template.
	 */
	public function renderConfirmModalTemplate(): void {
		if ( ! $this->contextResolver->isConfirmModalPage() ) {
			return;
		}
		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/confirm-modal-template.latte',
			[
				'translations' => [
					'closeModalPanel' => $this->wpAdapter->__( 'Close modal panel', 'packeta' ),
					'no'              => $this->wpAdapter->__( 'No', 'packeta' ),
					'yes'             => $this->wpAdapter->__( 'Yes', 'packeta' ),
				],
			]
		);
	}

	/**
	 * Print inactive WooCommerce notice.
	 *
	 * @return void
	 */
	public function echoInactiveWooCommerceNotice(): void {
		if ( $this->moduleHelper->isWooCommercePluginActive() === true ) {
			// When Packeta plugin is active and WooCommerce plugin is inactive.
			// If user decides to activate WooCommerce plugin then invalid notice will not be rendered.
			// Packeta plugin probably bootstraps twice in such case.
			return;
		}

		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/admin-notice.latte',
			[
				'message' => [
					'type'    => 'error',
					'message' => __( 'Packeta plugin requires WooCommerce. Please install and activate it first.', 'packeta' ),
				],
			]
		);
	}
}
