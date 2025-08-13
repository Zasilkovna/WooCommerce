<?php
/**
 * Class PacketAutoSubmitter
 *
 * @package Packetery\Module\Order
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\PaymentGatewayHelper;
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
	 * @var OptionsProvider
	 */
	private $optionsProvider;

	/**
	 * Packet submitter.
	 *
	 * @var PacketSubmitter
	 */
	private $packetSubmitter;

	/**
	 * Order repository.
	 *
	 * @var Repository
	 */
	private $orderRepository;

	/**
	 * Constructor.
	 *
	 * @param OptionsProvider $optionsProvider Options provider.
	 * @param PacketSubmitter $packetSubmitter Packet submitter.
	 * @param Repository      $orderRepository Order repository.
	 */
	public function __construct(
		OptionsProvider $optionsProvider,
		PacketSubmitter $packetSubmitter,
		Repository $orderRepository
	) {
		$this->optionsProvider = $optionsProvider;
		$this->packetSubmitter = $packetSubmitter;
		$this->orderRepository = $orderRepository;
	}

	/**
	 * Registers listeners.
	 *
	 * @return void
	 */
	public function register(): void {
		if ( $this->optionsProvider->isPacketAutoSubmissionEnabled() === false ) {
			return;
		}

		add_action( self::HOOK_NAME_HANDLE_EVENT, [ $this, 'handleEvent' ], 10, 2 );

		$mappedEvents = $this->optionsProvider->getPacketAutoSubmissionMappedUniqueEvents();
		foreach ( $mappedEvents as $mappedEvent ) {
			if ( $mappedEvent === self::EVENT_ON_ORDER_COMPLETED ) {
				add_action(
					'woocommerce_order_status_completed',
					function ( $orderId ): void {
						if ( ! is_int( $orderId ) ) {
							Module\WcLogger::logArgumentTypeError( __METHOD__, 'orderId', 'int', $orderId );

							return;
						}

						$this->handleEvent( self::EVENT_ON_ORDER_COMPLETED, $orderId );
					}
				);

				continue;
			}

			if ( $mappedEvent === self::EVENT_ON_ORDER_PROCESSING ) {
				add_action(
					'woocommerce_order_status_processing',
					function ( $orderId ): void {
						if ( ! is_int( $orderId ) ) {
							Module\WcLogger::logArgumentTypeError( __METHOD__, 'orderId', 'int', $orderId );

							return;
						}

						$this->handleEvent( self::EVENT_ON_ORDER_PROCESSING, $orderId );
					}
				);
			}
		}
	}

	/**
	 * Handle event.
	 *
	 * @param string|mixed $event   Event.
	 * @param int|mixed    $orderId WC Order.
	 * @return void
	 */
	public function handleEvent( string $event, int $orderId ): void {
		if ( $this->optionsProvider->isPacketAutoSubmissionEnabled() === false ) {
			return;
		}

		if ( ! is_string( $event ) ) {
			Module\WcLogger::logArgumentTypeError( __METHOD__, 'event', 'string', $event );

			return;
		}

		if ( ! is_int( $orderId ) ) {
			Module\WcLogger::logArgumentTypeError( __METHOD__, 'orderId', 'int', $orderId );

			return;
		}

		$wcOrder = $this->orderRepository->getWcOrderById( $orderId );
		assert( $wcOrder !== null, 'WC order has to be present' );

		$paymentGateway = wc_get_payment_gateway_by_order( $wcOrder );
		if (
			! $paymentGateway instanceof WC_Payment_Gateway ||
			array_key_exists( $paymentGateway->id, PaymentGatewayHelper::getAvailablePaymentGateways() ) === false
		) {
			return;
		}

		$mappingEventForGateway = $this->optionsProvider->getPacketAutoSubmissionEventForPaymentGateway(
			$this->optionsProvider->sanitizePaymentGatewayId( $paymentGateway->id )
		);
		if ( $mappingEventForGateway === null || $mappingEventForGateway !== $event ) {
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
		if ( $this->optionsProvider->isPacketAutoSubmissionEnabled() === false ) {
			return;
		}
		as_schedule_single_action( time(), self::HOOK_NAME_HANDLE_EVENT, [ $event, $orderId, is_admin() === false ] );
	}
}
