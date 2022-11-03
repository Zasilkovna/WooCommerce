<?php
/**
 * Class Synchronizer
 *
 * @package Packetery\Module\Order
 */

declare( strict_types=1 );


namespace Packetery\Module\Order;

use Packetery\Core\Api;
use Packetery\Core\Entity\PacketStatus;
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
	 * Gets packet statuses and default values.
	 *
	 * @return PacketStatus[]
	 */
	public static function getPacketStatuses(): array {
		return [
			PacketStatus::RECEIVED_DATA          =>
				new PacketStatus( PacketStatus::RECEIVED_DATA, __( 'Awaiting consignment', 'packeta' ), true ),
			PacketStatus::ARRIVED                =>
				new PacketStatus( PacketStatus::ARRIVED, __( 'Accepted at depot', 'packeta' ), true ),
			PacketStatus::PREPARED_FOR_DEPARTURE =>
				new PacketStatus( PacketStatus::PREPARED_FOR_DEPARTURE, __( 'On the way', 'packeta' ), true ),
			PacketStatus::DEPARTED               =>
				new PacketStatus( PacketStatus::DEPARTED, __( 'Departed from depot', 'packeta' ), true ),
			PacketStatus::READY_FOR_PICKUP       =>
				new PacketStatus( PacketStatus::READY_FOR_PICKUP, __( 'Ready for pick-up', 'packeta' ), true ),
			PacketStatus::HANDED_TO_CARRIER      =>
				new PacketStatus( PacketStatus::HANDED_TO_CARRIER, __( 'Handed over to carrier company', 'packeta' ), true ),
			PacketStatus::DELIVERED              =>
				new PacketStatus( PacketStatus::DELIVERED, __( 'Delivered', 'packeta' ), false ),
			PacketStatus::POSTED_BACK            =>
				new PacketStatus( PacketStatus::POSTED_BACK, __( 'Return (on the way back)', 'packeta' ), true ),
			PacketStatus::RETURNED               =>
				new PacketStatus( PacketStatus::RETURNED, __( 'Returned to sender', 'packeta' ), false ),
			PacketStatus::CANCELLED              =>
				new PacketStatus( PacketStatus::CANCELLED, __( 'Cancelled', 'packeta' ), false ),
			PacketStatus::COLLECTED              =>
				new PacketStatus( PacketStatus::COLLECTED, __( 'Parcel has been collected', 'packeta' ), true ),
			PacketStatus::UNKNOWN                =>
				new PacketStatus( PacketStatus::UNKNOWN, __( 'Unknown parcel status', 'packeta' ), false ),
		];
	}
}
