<?php
/**
 * Class Synchronizer
 *
 * @package Packetery\Module\Order
 */

declare( strict_types=1 );


namespace Packetery\Module\Order;

use Packetery\Core\Api;
use Packetery\Core\Log;
use Packetery\Module\Options;

/**
 * Class Synchronizer
 *
 * @package Packetery\Module\Order
 */
class PacketSynchronizer {

	/**
	 * API soap client.
	 *
	 * @var Api\Soap\Client
	 */
	private $apiSoapClient;

	/**
	 * Options provider.
	 *
	 * @var Options\Provider
	 */
	private $optionsProvider;

	/**
	 * Logger.
	 *
	 * @var Log\ILogger
	 */
	private $logger;

	/**
	 * Order repository.
	 *
	 * @var Repository
	 */
	private $orderRepository;

	/**
	 * Constructor.
	 *
	 * @param Api\Soap\Client  $apiSoapClient   API soap client.
	 * @param Log\ILogger      $logger          Logger.
	 * @param Options\Provider $optionsProvider Options provider.
	 * @param Repository       $orderRepository Order repository.
	 */
	public function __construct(
		Api\Soap\Client $apiSoapClient,
		Log\ILogger $logger,
		Options\Provider $optionsProvider,
		Repository $orderRepository
	) {
		$this->apiSoapClient   = $apiSoapClient;
		$this->logger          = $logger;
		$this->optionsProvider = $optionsProvider;
		$this->orderRepository = $orderRepository;
	}

	/**
	 * Synchronizes packets.
	 *
	 * @return void
	 */
	public function syncStatuses(): void {
		$results = $this->orderRepository->findStatusSyncingOrders(
			$this->optionsProvider->getStatusSyncingPacketStatuses(),
			$this->optionsProvider->getExistingStatusSyncingOrderStatuses(),
			$this->optionsProvider->getMaxDaysOfPacketStatusSyncing(),
			$this->optionsProvider->getMaxStatusSyncingPackets()
		);

		foreach ( $results as $order ) {
			$packetId = $order->getPacketId();

			$request  = new Api\Soap\Request\PacketStatus( (int) $packetId );
			$response = $this->apiSoapClient->packetStatus( $request );

			if ( $response->hasFault() ) {
				$record         = new Log\Record();
				$record->action = Log\Record::ACTION_PACKET_STATUS_SYNC;
				$record->status = Log\Record::STATUS_ERROR;
				$record->title  = __( 'packetStatusSyncErrorLogTitle', 'packetery' );
				$record->params = [
					'orderId'      => $order->getNumber(),
					'packetId'     => $request->getPacketId(),
					'errorMessage' => $response->getFaultString(),
				];
				$this->logger->add( $record );

				if ( $response->hasWrongPassword() ) {
					break;
				}

				continue;
			}

			if ( $response->getCodeText() === $order->getPacketStatus() ) {
				continue;
			}

			$order->setPacketStatus( $response->getCodeText() );
			$this->orderRepository->save( $order );
		}
	}

	/**
	 * Gets code text translated.
	 *
	 * @param string|null $packetStatus Packet status.
	 *
	 * @return string|null
	 */
	public function getPacketStatusTranslated( ?string $packetStatus ): string {
		switch ( $packetStatus ) {
			case 'received data':
				return __( 'packetStatusReceivedData', 'packetery' );
			case 'arrived':
				return __( 'packetStatusArrived', 'packetery' );
			case 'prepared for departure':
				return __( 'packetStatusPreparedForDeparture', 'packetery' );
			case 'departed':
				return __( 'packetStatusDeparted', 'packetery' );
			case 'ready for pickup':
				return __( 'packetStatusReadyForPickup', 'packetery' );
			case 'handed to carrier':
				return __( 'packetStatusHandedToCarrier', 'packetery' );
			case 'delivered':
				return __( 'packetStatusDelivered', 'packetery' );
			case 'posted back':
				return __( 'packetStatusPostedBack', 'packetery' );
			case 'returned':
				return __( 'packetStatusReturned', 'packetery' );
			case 'cancelled':
				return __( 'packetStatusCancelled', 'packetery' );
			case 'collected':
				return __( 'packetStatusCollected', 'packetery' );
			case 'unknown':
				return __( 'packetStatusUnknown', 'packetery' );
		}

		return (string) $packetStatus;
	}

	/**
	 * Gets packet statuses.
	 *
	 * @return string[]
	 */
	public function getPacketStatuses(): array {
		return [
			'received data',
			'arrived',
			'prepared for departure',
			'departed',
			'ready for pickup',
			'handed to carrier',
			'delivered',
			'posted back',
			'returned',
			'cancelled',
			'collected',
			'unknown',
		];
	}
}
