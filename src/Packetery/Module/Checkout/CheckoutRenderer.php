<?php

declare( strict_types=1 );

namespace Packetery\Module\Checkout;

use Packetery\Latte\Engine;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Order;
use Packetery\Module\Views\UrlBuilder;
use WC_Shipping_Rate;

class CheckoutRenderer {

	private const BUTTON_RENDERER_TABLE_ROW  = 'table-row';
	private const BUTTON_RENDERER_AFTER_RATE = 'after-rate';

	/**
	 * @var Engine
	 */
	private $latteEngine;

	/**
	 * @var UrlBuilder
	 */
	private $urlBuilder;

	/**
	 * @var CheckoutService
	 */
	private $checkoutService;

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	public function __construct(
		Engine $latteEngine,
		UrlBuilder $urlBuilder,
		CheckoutService $checkoutService,
		WpAdapter $wpAdapter
	) {
		$this->latteEngine     = $latteEngine;
		$this->urlBuilder      = $urlBuilder;
		$this->checkoutService = $checkoutService;
		$this->wpAdapter       = $wpAdapter;
	}

	/**
	 * Adds fields to the checkout page to save the values later
	 */
	public function renderHiddenInputFields(): void {
		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/checkout/input_fields.latte',
			[
				'fields' => array_unique(
					array_merge(
						array_column( Order\Attribute::$pickupPointAttrs, 'name' ),
						array_column( Order\Attribute::$homeDeliveryAttrs, 'name' ),
						array_column( Order\Attribute::$carDeliveryAttrs, 'name' )
					)
				),
			]
		);
	}

	/**
	 * Renders widget button and information about chosen pickup point
	 *
	 * @param WC_Shipping_Rate $shippingRate Shipping rate.
	 */
	public function renderWidgetButtonAfterShippingRate( WC_Shipping_Rate $shippingRate ): void {
		if ( ! is_checkout() ) {
			return;
		}

		if ( ! $this->checkoutService->isPacketeryShippingMethod( $shippingRate->get_id() ) ) {
			return;
		}

		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/checkout/widget-button.latte',
			[
				'renderer'     => self::BUTTON_RENDERER_AFTER_RATE,
				'logo'         => $this->urlBuilder->buildAssetUrl( 'public/images/packeta-symbol.png' ),
				'translations' => [
					'packeta' => $this->wpAdapter->__( 'Packeta', 'packeta' ),
				],
			]
		);
	}

	/**
	 * Render widget button table row.
	 *
	 * @return void
	 */
	public function renderWidgetButtonTableRow(): void {
		if ( ! is_checkout() ) {
			return;
		}

		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/checkout/widget-button-row.latte',
			[
				'renderer'     => self::BUTTON_RENDERER_TABLE_ROW,
				'logo'         => $this->urlBuilder->buildAssetUrl( 'public/images/packeta-symbol.png' ),
				'translations' => [
					'packeta' => $this->wpAdapter->__( 'Packeta', 'packeta' ),
				],
			]
		);
	}

	/**
	 * Shows an estimated delivery date for Car Delivery.
	 *
	 * @return void
	 */
	public function renderEstimatedDeliveryDateSection(): void {
		if ( ! is_checkout() ) {
			return;
		}

		if ( ! $this->checkoutService->isCarDeliveryOrder() ) {
			return;
		}

		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/checkout/car-delivery-estimated-delivery-date.latte'
		);
	}
}
