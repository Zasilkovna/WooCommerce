<?php

declare( strict_types=1 );

namespace Packetery\Module\Blocks;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use Packetery\Module\Blocks;
use Packetery\Module\Checkout\CheckoutSettings;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;

class BlockHooks {

	/**
	 * @var CheckoutSettings
	 */
	private $checkoutSettings;

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * @var WcAdapter
	 */
	private $wcAdapter;

	public function __construct(
		CheckoutSettings $checkoutSettings,
		WpAdapter $wpAdapter,
		WcAdapter $wcAdapter
	) {
		$this->checkoutSettings = $checkoutSettings;
		$this->wpAdapter        = $wpAdapter;
		$this->wcAdapter        = $wcAdapter;
	}

	public function registerCheckoutBlock( IntegrationRegistry $integrationRegistry ): void {
		$integrationRegistry->register(
			new Blocks\WidgetIntegration(
				$this->checkoutSettings->createSettings()
			)
		);
	}

	public function register(): void {
		// This hook is tested.
		$this->wpAdapter->addFilter(
			'__experimental_woocommerce_blocks_add_data_attributes_to_block',
			function ( $allowedBlocks ) {
				$allowedBlocks[] = 'packeta/packeta-widget';

				return $allowedBlocks;
			}
		);
		// This hook is expected replacement in the future.
		$this->wpAdapter->addFilter(
			'woocommerce_blocks_add_data_attributes_to_block',
			function ( $allowedBlocks ) {
				$allowedBlocks[] = 'packeta/packeta-widget';

				return $allowedBlocks;
			}
		);

		$this->wpAdapter->addAction(
			'woocommerce_blocks_checkout_block_registration',
			[
				$this,
				'registerCheckoutBlock',
			]
		);
	}

	public function saveShippingAndPaymentMethodsToSession( array $orderUpdateData ): void {
		if ( isset( $orderUpdateData['shipping_method'] ) ) {
			$this->wcAdapter->sessionSet( 'packetery_checkout_shipping_method', $orderUpdateData['shipping_method'] );
		}

		if ( isset( $orderUpdateData['payment_method'] ) ) {
			$this->wcAdapter->sessionSet( 'packetery_checkout_payment_method', $orderUpdateData['payment_method'] );
		}

		$this->wcAdapter->cartCalculateTotals();
	}

	public function orderUpdateCallback(): void {
		if ( ! function_exists( 'woocommerce_store_api_register_update_callback' ) ) {
			return;
		}
		$this->wcAdapter->storeApiRegisterUpdateCallback(
			[
				'namespace' => 'packetery-js-hooks',
				'callback'  => [ $this, 'saveShippingAndPaymentMethodsToSession' ],
			]
		);
	}
}
