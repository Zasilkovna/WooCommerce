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
use Packetery\Module\MessageManager;
use Packetery\Module\Options;
use PacketeryNette\Http\Request;
use Packetery\Core\Api\Soap;


/**
 * Class Synchronizer
 *
 * @package Packetery\Module\Order
 */
class PacketSynchronizer extends PacketActionsBase {

	/**
	 * SOAP API Client.
	 *
	 * @var Soap\Client SOAP API Client.
	 */
	protected $soapApiClient;

	/**
	 * Order repository.
	 *
	 * @var Repository
	 */
	protected $orderRepository;

	/**
	 * ILogger.
	 *
	 * @var Log\ILogger
	 */
	protected $logger;

	/**
	 * Request.
	 *
	 * @var Request
	 */
	protected $request;

	/**
	 * Message manager.
	 *
	 * @var MessageManager
	 */
	protected $messageManager;

	/**
	 * Options provider.
	 *
	 * @var Options\Provider
	 */
	private $optionsProvider;


	/**
	 * PacketSynchronizer constructor.
	 *
	 * @param Soap\Client      $soapApiClient   SOAP API Client.
	 * @param Repository       $orderRepository Order repository.
	 * @param Log\ILogger      $logger          Logger.
	 * @param Request          $request         Request.
	 * @param MessageManager   $messageManager  Message manager.
	 * @param Options\Provider $optionsProvider Options provider.
	 */
	public function __construct(
		Soap\Client $soapApiClient,
		Repository $orderRepository,
		Log\ILogger $logger,
		Request $request,
		MessageManager $messageManager,
		Options\Provider $optionsProvider
	) {
		parent::__construct( $soapApiClient, $orderRepository, $logger, $request, $messageManager );
		$this->optionsProvider = $optionsProvider;
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
			$response = $this->soapApiClient->packetStatus( $request );

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
			case 'prepared for departure':
			case 'departed':
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
