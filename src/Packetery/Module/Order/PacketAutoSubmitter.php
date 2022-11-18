<?php
/**
 * Class PacketAutoSubmitter
 *
 * @package Packetery\Module\Order
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Module;

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
	 * Constructor.
	 *
	 * @param Module\Options\Provider $optionsProvider Options provider.
	 * @param PacketSubmitter         $packetSubmitter Packet submitter.
	 * @param Module\Options\Page     $optionsPage Options page.
	 */
	public function __construct(
		Module\Options\Provider $optionsProvider,
		PacketSubmitter $packetSubmitter,
		Module\Options\Page $optionsPage
	) {
		$this->optionsProvider = $optionsProvider;
		$this->packetSubmitter = $packetSubmitter;
		$this->optionsPage     = $optionsPage;
	}

	/**
	 * Registers listeners.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( self::HOOK_NAME_HANDLE_EVENT, [ $this, 'handleEvent' ], 10, 2 );

		if ( false === $this->optionsProvider->isPacketAutoSubmissionEnabled() ) {
			return;
		}

		$mappedEvents = $this->optionsProvider->getPacketAutoSubmissionMappedUniqueEvents();
		foreach ( $mappedEvents as $mappedEvent ) {
			if ( self::EVENT_ON_ORDER_COMPLETED === $mappedEvent ) {
				add_action(
					'woocommerce_order_status_completed',
					function ( int $orderId ) {
						$this->handleEvent( self::EVENT_ON_ORDER_COMPLETED, $orderId );
					}
				);
				continue;
			}

			if ( self::EVENT_ON_ORDER_PROCESSING === $mappedEvent ) {
				add_action(
					'woocommerce_order_status_processing',
					function ( int $orderId ) {
						$this->handleEvent( self::EVENT_ON_ORDER_PROCESSING, $orderId );
					}
				);
			}
		}
	}

	/**
	 * Handle event.
	 *
	 * @param string $event   Event.
	 * @param int    $orderId WC Order.
	 *
	 * @return void
	 */
	public function handleEvent( string $event, int $orderId ): void {
		if ( false === $this->optionsProvider->isPacketAutoSubmissionEnabled() ) {
			return;
		}

		$wcOrder        = wc_get_order( $orderId );
		$paymentGateway = wc_get_payment_gateway_by_order( $wcOrder );
		if ( false === $this->optionsPage->hasPaymentGateway( $paymentGateway ) ) {
			return;
		}

		$mappingEventForGateway = $this->optionsProvider->getPacketAutoSubmissionEvenForPaymentGateway( $paymentGateway->id );
		if ( null === $mappingEventForGateway || $mappingEventForGateway !== $event ) {
			return;
		}

		$this->packetSubmitter->submitPacket( $wcOrder );
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
		as_schedule_single_action( time(), self::HOOK_NAME_HANDLE_EVENT, [ $event, $orderId ] );
	}
}
