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
				$record          = new Log\Record();
				$record->action  = Log\Record::ACTION_PACKET_STATUS_SYNC;
				$record->status  = Log\Record::STATUS_ERROR;
				$record->title   = __( 'Packet status could not be synchronized.', 'packeta' );
				$record->params  = [
					'orderId'      => $order->getNumber(),
					'packetId'     => $request->getPacketId(),
					'errorMessage' => $response->getFaultString(),
				];
				$record->orderId = $order->getNumber();
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
				return __( 'Awaiting consignment', 'packeta' );
			case 'arrived':
				return __( 'Accepted at depot', 'packeta' );
			case 'prepared for departure':
				return __( 'On the way', 'packeta' );
			case 'departed':
				return __( 'Departed from depot', 'packeta' );
			case 'collected':
				return __( 'On the way', 'packeta' );
			case 'ready for pickup':
				return __( 'Ready for pick-up', 'packeta' );
			case 'handed to carrier':
				return __( 'Handed over to carrier company', 'packeta' );
			case 'delivered':
				return __( 'Delivered', 'packeta' );
			case 'posted back':
				return __( 'Return (on the way back)', 'packeta' );
			case 'returned':
				return __( 'Returned to sender', 'packeta' );
			case 'cancelled':
				return __( 'Cancelled', 'packeta' );
			case 'unknown':
				return __( 'Unknown parcel status', 'packeta' );
		}

		return (string) $packetStatus;
	}

	/**
	 * Gets packet statuses and default values.
	 *
	 * @return string[]
	 */
	public static function getPacketStatuses(): array {
		return [
			'received data'          => true,
			'arrived'                => true,
			'prepared for departure' => true,
			'departed'               => true,
			'ready for pickup'       => true,
			'handed to carrier'      => true,
			'delivered'              => false,
			'posted back'            => true,
			'returned'               => false,
			'cancelled'              => false,
			'collected'              => true,
			'unknown'                => false,
		];
	}
}
