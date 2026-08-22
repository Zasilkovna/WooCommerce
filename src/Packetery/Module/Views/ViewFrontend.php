<?php

declare( strict_types=1 );

namespace Packetery\Module\Views;

use Packetery\Core\CoreHelper;
use Packetery\Core\Entity\Order;
use Packetery\Core\Entity\PacketReturn;
use Packetery\Latte\Engine;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Log\ArgumentTypeErrorLogger;
use Packetery\Module\Order\DetailCommonLogic;
use Packetery\Module\Order\Repository;
use Packetery\Module\Returns\Repository as ReturnRepository;
use Packetery\Module\Returns\ReturnEligibilityService;
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
	private ArgumentTypeErrorLogger $argumentTypeErrorLogger;
	private ReturnRepository $returnRepository;
	private ReturnEligibilityService $returnEligibilityService;
	private CoreHelper $coreHelper;

	public function __construct(
		Repository $orderRepository,
		Engine $latteEngine,
		DetailCommonLogic $detailCommonLogic,
		WpAdapter $wpAdapter,
		ArgumentTypeErrorLogger $argumentTypeErrorLogger,
		ReturnRepository $returnRepository,
		ReturnEligibilityService $returnEligibilityService,
		CoreHelper $coreHelper
	) {
		$this->orderRepository          = $orderRepository;
		$this->latteEngine              = $latteEngine;
		$this->detailCommonLogic        = $detailCommonLogic;
		$this->wpAdapter                = $wpAdapter;
		$this->argumentTypeErrorLogger  = $argumentTypeErrorLogger;
		$this->returnRepository         = $returnRepository;
		$this->returnEligibilityService = $returnEligibilityService;
		$this->coreHelper               = $coreHelper;
	}

	/**
	 * Renders delivery detail for packetery orders, on "thank you" page and in frontend detail.
	 *
	 * @param WC_Order|mixed $wcOrder WordPress order.
	 */
	public function renderOrderDetail( $wcOrder ): void {
		if ( ! $wcOrder instanceof WC_Order ) {
			$this->argumentTypeErrorLogger->log( __METHOD__, 'wcOrder', WC_Order::class, $wcOrder );

			return;
		}

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

		$this->renderReturnSection( $order, $wcOrder );
	}

	/**
	 * Renders the customer return section: existing returns and, when eligible, the submission form.
	 */
	private function renderReturnSection( Order $order, WC_Order $wcOrder ): void {
		$returnRows = [];
		foreach ( $this->returnRepository->getByOrderId( (string) $order->getNumber() ) as $return ) {
			$claimId      = $return->getPacketClaimId();
			$returnRows[] = [
				'barcode'     => $claimId !== null ? 'Z' . $claimId : null,
				'trackingUrl' => $claimId !== null ? $this->coreHelper->getTrackingUrl( $claimId ) : null,
				'status'      => $return->getStatus(),
			];
		}

		$canCreate = $this->returnEligibilityService->isReturnableByCustomer( $order, $wcOrder );
		if ( $returnRows === [] && ! $canCreate ) {
			return;
		}

		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/order/return-form.latte',
			[
				'orderId'      => (string) $order->getNumber(),
				'email'        => (string) $order->getEmail(),
				'phone'        => (string) $order->getPhone(),
				'canCreate'    => $canCreate,
				'returnRows'   => $returnRows,
				'translations' => [
					'heading'        => $this->wpAdapter->__( 'Returns', 'packeta' ),
					'existingReturn' => $this->wpAdapter->__( 'Return', 'packeta' ),
					'createReturn'   => $this->wpAdapter->__( 'Create a return', 'packeta' ),
					'emailLabel'     => $this->wpAdapter->__( 'Contact e-mail', 'packeta' ),
					'phoneLabel'     => $this->wpAdapter->__( 'Contact phone (optional)', 'packeta' ),
					'submit'         => $this->wpAdapter->__( 'Send the return', 'packeta' ),
					'returnStatuses' => [
						PacketReturn::STATUS_PENDING   => $this->wpAdapter->__( 'awaiting approval', 'packeta' ),
						PacketReturn::STATUS_CREATED   => $this->wpAdapter->__( 'created', 'packeta' ),
						PacketReturn::STATUS_CANCELLED => $this->wpAdapter->__( 'cancelled', 'packeta' ),
						PacketReturn::STATUS_REJECTED  => $this->wpAdapter->__( 'rejected', 'packeta' ),
					],
				],
			]
		);
	}
}
