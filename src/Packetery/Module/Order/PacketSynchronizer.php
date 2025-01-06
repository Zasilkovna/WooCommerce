<?php
/**
 * Class Synchronizer
 *
 * @package Packetery\Module\Order
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Api;
use Packetery\Core\Entity\Order;
use Packetery\Core\Entity\PacketStatus;
use Packetery\Core\Log;
use Packetery\Module\Exception\InvalidPasswordException;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Options\OptionsProvider;

/**
 * Class Synchronizer
 *
 * @package Packetery\Module\Order
 */
class PacketSynchronizer {

	private const HOOK_NAME_SYNC_ORDER_STATUS = 'packetery_sync_order_status';

	/**
	 * API soap client.
	 *
	 * @var Api\Soap\Client
	 */
	private $apiSoapClient;

	/**
	 * Options provider.
	 *
	 * @var OptionsProvider
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
	 * WC order actions.
	 *
	 * @var WcOrderActions
	 */
	private $wcOrderActions;

	/**
	 * WC adapter.
	 *
	 * @var WcAdapter
	 */
	private $wcAdapter;

	/**
	 * Constructor.
	 *
	 * @param Api\Soap\Client $apiSoapClient   API soap client.
	 * @param Log\ILogger     $logger          Logger.
	 * @param OptionsProvider $optionsProvider Options provider.
	 * @param Repository      $orderRepository Order repository.
	 * @param WcOrderActions  $wcOrderActions  WC order actions.
	 * @param WcAdapter       $wcAdapter       WC adapter.
	 */
	public function __construct(
		Api\Soap\Client $apiSoapClient,
		Log\ILogger $logger,
		OptionsProvider $optionsProvider,
		Repository $orderRepository,
		WcOrderActions $wcOrderActions,
		WcAdapter $wcAdapter
	) {
		$this->apiSoapClient   = $apiSoapClient;
		$this->logger          = $logger;
		$this->optionsProvider = $optionsProvider;
		$this->orderRepository = $orderRepository;
		$this->wcOrderActions  = $wcOrderActions;
		$this->wcAdapter       = $wcAdapter;
	}

	/**
	 * Register hook.
	 *
	 * @return void
	 */
	public function register() {
		add_action( self::HOOK_NAME_SYNC_ORDER_STATUS, [ $this, 'syncStatusById' ] );
	}

	/**
	 * Synchronizes packets.
	 *
	 * @return void
	 * @throws \Exception Exception.
	 */
	public function syncStatuses(): void {
		$syncingOrderIds = $this->orderRepository->findStatusSyncingOrderIds(
			$this->optionsProvider->getStatusSyncingPacketStatuses(),
			$this->optionsProvider->getExistingStatusSyncingOrderStatuses(),
			$this->optionsProvider->getMaxDaysOfPacketStatusSyncing(),
			$this->optionsProvider->getMaxStatusSyncingPackets()
		);

		foreach ( $syncingOrderIds as $orderId ) {
			$this->wcAdapter->asScheduleSingleAction( time(), self::HOOK_NAME_SYNC_ORDER_STATUS, [ $orderId ] );
		}
	}

	/**
	 * Synchronizes status for one order.
	 *
	 * @param int $orderId Order id.
	 *
	 * @return void
	 */
	public function syncStatusById( int $orderId ): void {
		$order = $this->orderRepository->getById( $orderId );
		if ( $order === null ) {
			return;
		}

		$this->syncStatus( $order );
	}

	/**
	 * Synchronizes status for one order.
	 *
	 * @param Order $order Order.
	 *
	 * @return void
	 * @throws InvalidPasswordException InvalidPasswordException.
	 */
	public function syncStatus( Order $order ): void {
		$packetId = $order->getPacketId();

		$request  = new Api\Soap\Request\PacketStatus( $packetId );
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
				throw new InvalidPasswordException( 'Wrong password.' );
			}

			return;
		}

		$storedUntilResponse = $response->getStoredUntil();
		$storedUntilOrder    = $order->getStoredUntil();

		if ( $storedUntilResponse === $storedUntilOrder && $response->getCodeText() === $order->getPacketStatus() ) {
			return;
		}

		if ( $storedUntilResponse !== $storedUntilOrder ) {
			$order->setStoredUntil( $storedUntilResponse );
		}

		if ( $response->getCodeText() !== $order->getPacketStatus() ) {
			$order->setPacketStatus( $response->getCodeText() );
			$this->wcOrderActions->updateOrderStatus( $order->getNumber(), $response->getCodeText() );
		}

		$this->orderRepository->save( $order );
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
			PacketStatus::CUSTOMS                =>
				new PacketStatus( PacketStatus::CUSTOMS, __( 'Customs declaration process', 'packeta' ), true ),
			PacketStatus::REVERSE_PACKET_ARRIVED =>
				new PacketStatus( PacketStatus::REVERSE_PACKET_ARRIVED, __( 'Reverse parcel has been accepted at our pick up point', 'packeta' ), true ),
			PacketStatus::DELIVERY_ATTEMPT       =>
				new PacketStatus( PacketStatus::DELIVERY_ATTEMPT, __( 'Unsuccessful delivery attempt of parcel', 'packeta' ), true ),
			PacketStatus::REJECTED_BY_RECIPIENT  =>
				new PacketStatus( PacketStatus::REJECTED_BY_RECIPIENT, __( 'Rejected by recipient response', 'packeta' ), true ),
			PacketStatus::UNKNOWN                =>
				new PacketStatus( PacketStatus::UNKNOWN, __( 'Unknown parcel status', 'packeta' ), false ),
		];
	}
}
