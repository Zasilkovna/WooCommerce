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
use Packetery\Core\Entity\PacketStatus;
use Packetery\Core\Log;
use Packetery\Module\ModuleHelper;
use Packetery\Module\MessageManager;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Nette\Http\Request;

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
	 * @var OptionsProvider
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
	 * WC order actions.
	 *
	 * @var WcOrderActions
	 */
	private $wcOrderActions;

	/**
	 * ModuleHelper.
	 *
	 * @var ModuleHelper
	 */
	private $moduleHelper;

	/**
	 * Constructor.
	 *
	 * @param Soap\Client              $soapApiClient   Soap client API.
	 * @param Log\ILogger              $logger          Logger.
	 * @param Repository               $orderRepository Order repository.
	 * @param Request                  $request         Request.
	 * @param OptionsProvider          $optionsProvider Options provider.
	 * @param MessageManager           $messageManager  Message manager.
	 * @param PacketActionsCommonLogic $commonLogic     Common logic.
	 * @param WcOrderActions           $wcOrderActions  WC order actions.
	 * @param ModuleHelper             $moduleHelper    ModuleHelper.
	 */
	public function __construct(
		Soap\Client $soapApiClient,
		Log\ILogger $logger,
		Repository $orderRepository,
		Request $request,
		OptionsProvider $optionsProvider,
		MessageManager $messageManager,
		PacketActionsCommonLogic $commonLogic,
		WcOrderActions $wcOrderActions,
		ModuleHelper $moduleHelper
	) {
		$this->soapApiClient   = $soapApiClient;
		$this->logger          = $logger;
		$this->orderRepository = $orderRepository;
		$this->request         = $request;
		$this->optionsProvider = $optionsProvider;
		$this->messageManager  = $messageManager;
		$this->commonLogic     = $commonLogic;
		$this->wcOrderActions  = $wcOrderActions;
		$this->moduleHelper    = $moduleHelper;
	}

	/**
	 * Process action.
	 *
	 * @return void
	 */
	public function processAction(): void {
		$order      = $this->commonLogic->getOrder();
		$redirectTo = $this->request->getQuery( PacketActionsCommonLogic::PARAM_REDIRECT_TO );
		$packetId   = $this->request->getQuery( PacketActionsCommonLogic::PARAM_PACKET_ID );

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

		$this->cancelPacket( $order, $packetId );
		$this->commonLogic->redirectTo( $redirectTo, $order );
	}

	/**
	 * Cancels single packet.
	 *
	 * @param Entity\Order $order Order ID.
	 * @param string|null  $packetId Packet ID.
	 *
	 * @return void
	 */
	public function cancelPacket( Entity\Order $order, ?string $packetId ): void {
		if ( null === $packetId ) {
			$record          = new Log\Record();
			$record->action  = Log\Record::ACTION_PACKET_CANCEL;
			$record->status  = Log\Record::STATUS_ERROR;
			$record->orderId = $order->getNumber();
			$record->title   = __( 'Packet cancel error', 'packeta' );
			$record->params  = [
				'orderId'      => $order->getNumber(),
				'packetId'     => $packetId,
				'referer'      => (string) $this->request->getReferer(),
				'errorMessage' => 'Packet could not be cancelled',
			];

			$this->logger->add( $record );

			$this->messageManager->flash_message( __( 'Packet could not be cancelled', 'packeta' ), MessageManager::TYPE_ERROR );
			return;
		}

		$request = new Soap\Request\CancelPacket( $packetId );
		$result  = $this->soapApiClient->cancelPacket( $request );

		if ( ! $result->hasFault() ) {
			$record          = new Log\Record();
			$record->action  = Log\Record::ACTION_PACKET_CANCEL;
			$record->status  = Log\Record::STATUS_SUCCESS;
			$record->orderId = $order->getNumber();
			$record->title   = __( 'Packet cancel success', 'packeta' );
			$record->params  = [
				'orderId'  => $order->getNumber(),
				'packetId' => $packetId,
			];

			$this->logger->add( $record );
			$errorMessage = null;

			$wcOrder = $this->orderRepository->getWcOrderById( (int) $order->getNumber() );
			if ( null !== $wcOrder ) {
				// translators: %s represents a packet tracking link.
				$message     = __( 'Packeta: Packet %s has been cancelled', 'packeta' );
				$trackingUrl = $order->getPacketTrackingUrl();
				$text        = $order->getPacketBarcode();
				if ( $order->isPacketClaim( $packetId ) ) {
					// translators: %s represents a packet tracking link.
					$message     = __( 'Packeta: Packet claim %s has been cancelled', 'packeta' );
					$trackingUrl = $order->getPacketClaimTrackingUrl();
					$text        = $order->getPacketClaimBarcode();
				}

				$wcOrder->add_order_note(
					sprintf( $message, $this->moduleHelper->createHtmlLink( $trackingUrl, $text ) )
				);
				$wcOrder->save();
			}
		}

		if ( $result->hasFault() ) {
			$record          = new Log\Record();
			$record->action  = Log\Record::ACTION_PACKET_CANCEL;
			$record->status  = Log\Record::STATUS_ERROR;
			$record->orderId = $order->getNumber();
			$record->title   = __( 'Packet cancel error', 'packeta' );
			$record->params  = [
				'orderId'      => $order->getNumber(),
				'packetId'     => $packetId,
				'errorMessage' => $result->getFaultString(),
			];

			$this->logger->add( $record );
			$errorMessage = $result->getFaultString();
		}

		$order->updateApiErrorMessage( $errorMessage );

		if ( $packetId === $order->getPacketId() && $this->shouldRevertSubmission( $result ) ) {
			$order->setIsExported( false );
			$order->setIsLabelPrinted( false );
			$order->setCarrierNumber( null );
			$order->setPacketStatus( null );
			$this->wcOrderActions->updateOrderStatus( $order->getNumber(), PacketStatus::CANCELLED );
			$order->setPacketId( null );
			$order->updateApiErrorMessage( null );

			if ( $result->hasFault() ) {
				$this->messageManager->flash_message( __( 'Packet could not be canceled in the Packeta system, packet was canceled only in the order list.', 'packeta' ), MessageManager::TYPE_SUCCESS );
			}

			if ( ! $result->hasFault() ) {
				$this->messageManager->flash_message( __( 'Packet has been successfully canceled both in the order list and the Packeta system.', 'packeta' ), MessageManager::TYPE_SUCCESS );
			}
		}

		if ( $packetId === $order->getPacketClaimId() && $this->shouldRevertSubmission( $result ) ) {
			$order->setPacketClaimId( null );
			$order->setPacketClaimPassword( null );
			$order->updateApiErrorMessage( null );

			if ( $result->hasFault() ) {
				$this->messageManager->flash_message( __( 'Packet claim could not be canceled in the Packeta system, packet was canceled only in the order list.', 'packeta' ), MessageManager::TYPE_SUCCESS );
			}

			if ( ! $result->hasFault() ) {
				$this->messageManager->flash_message( __( 'Packet claim has been successfully canceled both in the order list and the Packeta system.', 'packeta' ), MessageManager::TYPE_SUCCESS );
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
