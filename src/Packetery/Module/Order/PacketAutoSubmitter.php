<?php
/**
 * Class PacketAutoSubmitter
 *
 * @package Packetery\Module\Order
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Module;
use WC_Payment_Gateway;

/**
 * Class PacketAutoSubmitter
 *
 * @package Packetery\Module\Order
 */
class PacketAutoSubmitter {

	private const HOOK_NAME_HANDLE_EVENT = 'packetery_auto_submission_handle_event';

	public const EVENT_ON_ORDER_COMPLETED   = 'onOrderCompleted';
	public const EVENT_ON_ORDER_PROCESSING  = 'onOrderProcessing';
	public const EVENT_ON_ORDER_CREATION_FE = 'onOrderCreationFE';

	/**
	 * Options provider.
	 *
	 * @var Module\Options\Provider
	 */
	private $optionsProvider;

	/**
	 * Packet submitter.
	 *
	 * @var PacketSubmitter
	 */
	private $packetSubmitter;

	/**
	 * Options page.
	 *
	 * @var Module\Options\Page
	 */
	private $optionsPage;

	/**
	 * Order repository.
	 *
	 * @var Repository
	 */
	private $orderRepository;

	/**
	 * Constructor.
	 *
	 * @param Module\Options\Provider $optionsProvider Options provider.
	 * @param PacketSubmitter         $packetSubmitter Packet submitter.
	 * @param Module\Options\Page     $optionsPage     Options page.
	 * @param Repository              $orderRepository Order repository.
	 */
	public function __construct(
		Module\Options\Provider $optionsProvider,
		PacketSubmitter $packetSubmitter,
		Module\Options\Page $optionsPage,
		Repository $orderRepository
	) {
		$this->optionsProvider = $optionsProvider;
		$this->packetSubmitter = $packetSubmitter;
		$this->optionsPage     = $optionsPage;
		$this->orderRepository = $orderRepository;
	}

	/**
	 * Registers listeners.
	 *
	 * @return void
	 */
	public function register(): void {
		if ( false === $this->optionsProvider->isPacketAutoSubmissionEnabled() ) {
			return;
		}

		add_action( self::HOOK_NAME_HANDLE_EVENT, [ $this, 'handleEvent' ], 10, 3 );

		$mappedEvents = $this->optionsProvider->getPacketAutoSubmissionMappedUniqueEvents();
		foreach ( $mappedEvents as $mappedEvent ) {
			if ( self::EVENT_ON_ORDER_COMPLETED === $mappedEvent ) {
				add_action(
					'woocommerce_order_status_completed',
					function ( int $orderId ): void {
						$this->handleEvent( self::EVENT_ON_ORDER_COMPLETED, $orderId, is_admin() === false );
					}
				);
				continue;
			}

			if ( self::EVENT_ON_ORDER_PROCESSING === $mappedEvent ) {
				add_action(
					'woocommerce_order_status_processing',
					function ( int $orderId ): void {
						$this->handleEvent( self::EVENT_ON_ORDER_PROCESSING, $orderId, is_admin() === false );
					}
				);
			}
		}
	}

	/**
	 * Handle event.
	 *
	 * @param string    $event               Event.
	 * @param int       $orderId             WC Order.
	 * @param bool|null $triggeredByFrontend Tells if event is triggered by frontend logic. NULL is passed when data from previous versions are being processed.
	 *
	 * @return void
	 */
	public function handleEvent( string $event, int $orderId, ?bool $triggeredByFrontend = null ): void {
		if ( false === $this->optionsProvider->isPacketAutoSubmissionEnabled() ) {
			return;
		}

		$wcOrder = $this->orderRepository->getWcOrderById( $orderId );
		assert( null !== $wcOrder, 'WC order has to be present' );

		$paymentGateway = wc_get_payment_gateway_by_order( $wcOrder );
		if ( ! $paymentGateway instanceof WC_Payment_Gateway || false === $this->optionsPage->hasPaymentGateway( $paymentGateway ) ) {
			return;
		}

		$mappingEventForGateway = $this->optionsProvider->getPacketAutoSubmissionEventForPaymentGateway(
			$this->optionsProvider->sanitizePaymentGatewayId( $paymentGateway->id )
		);
		if ( null === $mappingEventForGateway || $mappingEventForGateway !== $event ) {
			return;
		}

		$this->packetSubmitter->submitPacket(
			$wcOrder,
			$this->shouldUpdateOrderStatus( $triggeredByFrontend )
		);
	}

	/**
	 * Tells if WC order status should be updated.
	 *
	 * @param bool|null $triggeredByFrontend Tells if submission is triggered by frontend action.
	 *
	 * @return bool
	 */
	private function shouldUpdateOrderStatus( ?bool $triggeredByFrontend ): bool {
		return ( false === $triggeredByFrontend && $this->optionsProvider->isOrderStatusAutoChangeEnabled() ) ||
			( true === $triggeredByFrontend && $this->optionsProvider->isOrderStatusAutoChangeForAutoSubmitAtFrontendEnabled() );
	}

	/**
	 * Handles event async.
	 *
	 * @param string $event Event.
	 * @param int    $orderId Order ID.
	 *
	 * @return void
	 */
	public function handleEventAsync( string $event, int $orderId ): void {
		if ( false === $this->optionsProvider->isPacketAutoSubmissionEnabled() ) {
			return;
		}
		as_schedule_single_action( time(), self::HOOK_NAME_HANDLE_EVENT, [ $event, $orderId, is_admin() === false ] );
	}

}
