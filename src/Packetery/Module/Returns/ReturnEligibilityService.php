<?php
/**
 * Class ReturnEligibilityService.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Returns;

use Packetery\Core\CoreHelper;
use Packetery\Core\Entity\Order;
use Packetery\Core\Entity\PacketStatus;
use Packetery\Core\Returns\ReturnableOrderInfo;
use Packetery\Core\Returns\ReturnEligibility;
use Packetery\Module\Carrier\CarrierOptionsFactory;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Product;
use WC_Order;
use WC_Order_Item_Product;
use WC_Product;

/**
 * Module-level gate answering whether a customer may create a return for a given Packeta order.
 *
 * Collects the order facts from the Packeta {@see Order} entity and the {@see WC_Order} and feeds them
 * into the pure {@see ReturnEligibility} engine. Non-Packeta orders (which have no Packeta entity) are
 * handled elsewhere.
 */
class ReturnEligibilityService {

	private ReturnEligibility $eligibility;
	private OptionsProvider $optionsProvider;
	private CarrierOptionsFactory $carrierOptionsFactory;

	public function __construct(
		ReturnEligibility $eligibility,
		OptionsProvider $optionsProvider,
		CarrierOptionsFactory $carrierOptionsFactory
	) {
		$this->eligibility           = $eligibility;
		$this->optionsProvider       = $optionsProvider;
		$this->carrierOptionsFactory = $carrierOptionsFactory;
	}

	/**
	 * Whether the customer may create a return for this Packeta order (service enabled and all
	 * configured eligibility restrictions met).
	 */
	public function isReturnableByCustomer( Order $order, WC_Order $wcOrder ): bool {
		if ( ! $this->optionsProvider->isReturnsEnabled() ) {
			return false;
		}

		return $this->eligibility->isEligible(
			$this->createOrderInfo( $order, $wcOrder ),
			$this->optionsProvider->getReturnSettings(),
			CoreHelper::now()
		);
	}

	private function createOrderInfo( Order $order, WC_Order $wcOrder ): ReturnableOrderInfo {
		$categoryIds    = [];
		$hasVirtualItem = false;
		foreach ( $wcOrder->get_items() as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}
			$product = $item->get_product();
			if ( ! $product instanceof WC_Product ) {
				continue;
			}
			$categoryIds = array_merge( $categoryIds, $product->get_category_ids() );
			if ( ! ( new Product\Entity( $product ) )->isPhysical() ) {
				$hasVirtualItem = true;
			}
		}

		$carrierOptions = $this->carrierOptionsFactory->createByCarrierId( $order->getCarrier()->getId() );

		return new ReturnableOrderInfo(
			$wcOrder->get_status(),
			$this->resolveCompletedAt( $wcOrder ),
			$order->getShippingCountry(),
			true,
			$order->getPacketStatus() === PacketStatus::DELIVERED,
			$carrierOptions->allowsReturns(),
			$order->getFinalValue(),
			$order->getFinalWeight(),
			array_values( array_unique( array_map( 'intval', $categoryIds ) ) ),
			$hasVirtualItem
		);
	}

	private function resolveCompletedAt( WC_Order $wcOrder ): ?\DateTimeImmutable {
		$dateCompleted = $wcOrder->get_date_completed();
		if ( ! $dateCompleted instanceof \DateTime ) {
			return null;
		}

		return \DateTimeImmutable::createFromMutable( $dateCompleted );
	}
}
