<?php

declare( strict_types=1 );

namespace Packetery\Module\Views;

use Packetery\Latte\Engine;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Order\DetailCommonLogic;
use Packetery\Module\Order\Repository;
use WC_Order;

class ViewFrontend {

	/**
	 * @var Repository
	 */
	private $orderRepository;

	/**
	 * @var Engine
	 */
	private $latteEngine;

	/**
	 * @var DetailCommonLogic
	 */
	private $detailCommonLogic;

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	public function __construct(
		Repository $orderRepository,
		Engine $latteEngine,
		DetailCommonLogic $detailCommonLogic,
		WpAdapter $wpAdapter
	) {
		$this->orderRepository   = $orderRepository;
		$this->latteEngine       = $latteEngine;
		$this->detailCommonLogic = $detailCommonLogic;
		$this->wpAdapter         = $wpAdapter;
	}

	/**
	 * Renders delivery detail for packetery orders, on "thank you" page and in frontend detail.
	 *
	 * @param WC_Order $wcOrder WordPress order.
	 */
	public function renderOrderDetail( WC_Order $wcOrder ): void {
		$order = $this->orderRepository->getByWcOrderWithValidCarrier( $wcOrder );
		if ( $order === null ) {
			return;
		}

		if ( $this->detailCommonLogic->shouldHidePacketaInfo( $order ) ) {
			return;
		}

		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/order/detail.latte',
			[
				'displayPickupPointInfo' => $this->detailCommonLogic->shouldDisplayPickupPointInfo(),
				'order'                  => $order,
				'translations'           => [
					'packeta'              => $this->wpAdapter->__( 'Packeta', 'packeta' ),
					'pickupPointName'      => $this->wpAdapter->__( 'Pickup Point Name', 'packeta' ),
					'pickupPointDetail'    => $this->wpAdapter->__( 'Pickup Point Detail', 'packeta' ),
					'address'              => $this->wpAdapter->__( 'Address', 'packeta' ),
					'packetTrackingOnline' => $this->wpAdapter->__( 'Packet tracking online', 'packeta' ),
				],
			]
		);
	}
}
