<?php
/**
 * Class PacketCanceller
 *
 * @package Packetery\Module\Order
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Api\Soap;
use Packetery\Core\Entity;
use Packetery\Core\Log;
use Packetery\Module\MessageManager;
use Packetery\Module\Options;
use PacketeryNette\Http\Request;

/**
 * Class PacketCanceller
 *
 * @package Packetery\Module\Order
 */
class PacketCanceller {

	/**
	 * SOAP API Client.
	 *
	 * @var Soap\Client SOAP API Client.
	 */
	private $soapApiClient;

	/**
	 * Order repository.
	 *
	 * @var Repository
	 */
	private $orderRepository;

	/**
	 * ILogger.
	 *
	 * @var Log\ILogger
	 */
	private $logger;

	/**
	 * Request.
	 *
	 * @var Request
	 */
	private $request;

	/**
	 * Options provider.
	 *
	 * @var Options\Provider
	 */
	private $optionsProvider;

	/**
	 * Message manager.
	 *
	 * @var MessageManager
	 */
	private $messageManager;

	/**
	 * Common logic.
	 *
	 * @var PacketActionsCommonLogic
	 */
	private $commonLogic;

	/**
	 * Constructor.
	 *
	 * @param Soap\Client              $soapApiClient   Soap client API.
	 * @param Log\ILogger              $logger          Logger.
	 * @param Repository               $orderRepository Order repository.
	 * @param Request                  $request         Request.
	 * @param Options\Provider         $optionsProvider Options provider.
	 * @param MessageManager           $messageManager  Message manager.
	 * @param PacketActionsCommonLogic $commonLogic     Common logic.
	 */
	public function __construct(
		Soap\Client $soapApiClient,
		Log\ILogger $logger,
		Repository $orderRepository,
		Request $request,
		Options\Provider $optionsProvider,
		MessageManager $messageManager,
		PacketActionsCommonLogic $commonLogic
	) {
		$this->soapApiClient   = $soapApiClient;
		$this->logger          = $logger;
		$this->orderRepository = $orderRepository;
		$this->request         = $request;
		$this->optionsProvider = $optionsProvider;
		$this->messageManager  = $messageManager;
		$this->commonLogic     = $commonLogic;
	}

	/**
	 * Process action.
	 *
	 * @return void
	 */
	public function processAction(): void {
		$order      = $this->commonLogic->getOrder();
		$redirectTo = $this->request->getQuery( PacketActionsCommonLogic::PARAM_REDIRECT_TO );

		if ( null === $order ) {
			$record          = new Log\Record();
			$record->action  = Log\Record::ACTION_PACKET_CANCEL;
			$record->status  = Log\Record::STATUS_ERROR;
			$record->orderId = null;
			$record->title   = __( 'Packet cancel error', 'packeta' );
			$record->params  = [
				'referer'      => (string) $this->request->getReferer(),
				'errorMessage' => 'Order not found',
			];

			$this->logger->add( $record );

			$this->messageManager->flash_message( __( 'Order not found', 'packeta' ), MessageManager::TYPE_ERROR );
			$this->commonLogic->redirectTo( $redirectTo, $order );
			return;
		}

		$this->commonLogic->checkAction( PacketActionsCommonLogic::ACTION_CANCEL_PACKET, $order );

		$this->cancelPacket( $order );
		$this->commonLogic->redirectTo( $redirectTo, $order );
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

		$record         = new Log\Record();
		$record->action = Log\Record::ACTION_PACKET_CANCEL;
		if ( ! $result->hasFault() ) {
			$record->status  = Log\Record::STATUS_SUCCESS;
			$record->orderId = $order->getNumber();
			$record->title   = __( 'Packet cancel success', 'packeta' );
			$record->params  = [
				'orderId'  => $order->getNumber(),
				'packetId' => $order->getPacketId(),
			];
		} else {
			$record->status  = Log\Record::STATUS_ERROR;
			$record->orderId = $order->getNumber();
			$record->title   = __( 'Packet cancel error', 'packeta' );
			$record->params  = [
				'orderId'      => $order->getNumber(),
				'packetId'     => $order->getPacketId(),
				'errorMessage' => $result->getFaultString(),
			];
		}
		$this->logger->add( $record );
		$order->updateApiErrorMessage( $result->getFaultString() );

		if ( $this->shouldRevertSubmission( $result ) ) {
			$order->setIsExported( false );
			$order->setIsLabelPrinted( false );
			$order->setCarrierNumber( null );
			$order->setPacketStatus( null );
			$order->setPacketId( null );

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

		$this->orderRepository->save( $order );
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
