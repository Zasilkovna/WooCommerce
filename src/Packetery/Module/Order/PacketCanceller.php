<?php
/**
 * Class PacketCanceller
 *
 * @package Packetery\Api
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Api\Soap;
use Packetery\Core\Entity;
use Packetery\Core\Log;
use Packetery\Module\MessageManager;
use Packetery\Module\Options;
use Packetery\Module\Plugin;
use PacketeryNette\Http\Request;

/**
 * Class PacketCanceller
 *
 * @package Packetery\Api
 */
class PacketCanceller extends PacketActionsBase {

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
	 * Constructor.
	 *
	 * @param Soap\Client      $soapApiClient   Soap client API.
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
	 * Process action.
	 *
	 * @return void
	 */
	public function processAction(): void {
		$this->setAction( self::ACTION_CANCEL_PACKET );
		$this->setLogAction( Log\Record::ACTION_PACKET_CANCEL );
		parent::processAction();

		$this->cancelPacket( $this->order );
		$redirectTo = $this->request->getQuery( self::PARAM_REDIRECT_TO );
		$this->redirectTo( $redirectTo, $this->order );
	}

	/**
	 * Cancels single packet.
	 *
	 * @param Entity\Order $order Order ID.
	 *
	 * @return void
	 */
	public function cancelPacket( Entity\Order $order ): void {
		if ( null === $order->getPacketId() ) {
			$record          = new Log\Record();
			$record->action  = Log\Record::ACTION_PACKET_CANCEL;
			$record->status  = Log\Record::STATUS_ERROR;
			$record->orderId = $order->getNumber();
			$record->title   = __( 'Packet cancel error', 'packeta' );
			$record->params  = [
				'orderId'      => $order->getNumber(),
				'packetId'     => $order->getPacketId(),
				'referer'      => (string) $this->request->getReferer(),
				'errorMessage' => 'Packet could not be cancelled',
			];

			$this->logger->add( $record );

			$this->messageManager->flash_message( __( 'Packet could not be cancelled', 'packeta' ), MessageManager::TYPE_ERROR );
			return;
		}

		$request = new Soap\Request\CancelPacket( (int) $order->getPacketId() );
		$result  = $this->soapApiClient->cancelPacket( $request );

		if ( ! $result->hasFault() ) {
			$record          = new Log\Record();
			$record->action  = Log\Record::ACTION_PACKET_CANCEL;
			$record->status  = Log\Record::STATUS_SUCCESS;
			$record->orderId = $order->getNumber();
			$record->title   = __( 'Packet cancel success', 'packeta' );
			$record->params  = [
				'orderId'  => $order->getNumber(),
				'packetId' => $order->getPacketId(),
			];

			$this->logger->add( $record );
		}

		if ( $result->hasFault() ) {
			$record          = new Log\Record();
			$record->action  = Log\Record::ACTION_PACKET_CANCEL;
			$record->status  = Log\Record::STATUS_ERROR;
			$record->orderId = $order->getNumber();
			$record->title   = __( 'Packet cancel error', 'packeta' );
			$record->params  = [
				'orderId'      => $order->getNumber(),
				'packetId'     => $order->getPacketId(),
				'errorMessage' => $result->getFaultString(),
			];

			$this->logger->add( $record );
		}

		if ( $this->shouldRevertSubmission( $result ) ) {
			$order->setIsExported( false );
			$order->setIsLabelPrinted( false );
			$order->setCarrierNumber( null );
			$order->setPacketStatus( null );
			$order->setPacketId( null );
			$this->orderRepository->save( $order );

			if ( $result->hasFault() ) {
				$this->messageManager->flash_message( __( 'Packet could not be canceled in the Packeta system, packet was canceled only in the order list.', 'packeta' ), MessageManager::TYPE_SUCCESS );
			}

			if ( ! $result->hasFault() ) {
				$this->messageManager->flash_message( __( 'Packet has been successfully canceled both in the order list and the Packeta system.', 'packeta' ), MessageManager::TYPE_SUCCESS );
			}
		}

		if ( ! $this->shouldRevertSubmission( $result ) ) {
			$this->messageManager->flash_message( __( 'Failed to cancel packet. See Packeta log for more details.', 'packeta' ), MessageManager::TYPE_ERROR );
		}
	}

	/**
	 * Should revert local order submission to Packeta.
	 *
	 * @param Soap\Response\CancelPacket|null $result Packeta API result.
	 *
	 * @return bool
	 */
	public function shouldRevertSubmission( ?Soap\Response\CancelPacket $result ): bool {
		if ( null === $result ) {
			return false;
		}

		$revertSubmission = ! $result->hasFault();

		if ( $result->hasCancelNotAllowedFault() && $this->optionsProvider->isPacketCancellationForced() ) {
			$revertSubmission = true;
		}

		return $revertSubmission;
	}
}
