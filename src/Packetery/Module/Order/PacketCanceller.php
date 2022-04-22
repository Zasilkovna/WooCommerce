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
class PacketCanceller {

	public const ACTION_CANCEL_PACKET     = 'cancel_packet';
	public const PARAM_ORDER_ID           = 'order_id';
	public const PARAM_REDIRECT_TO        = 'packetery_redirect_to';
	public const REDIRECT_TO_ORDER_GRID   = 'order-grid';
	public const REDIRECT_TO_ORDER_DETAIL = 'order-detail';

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
	 * Constructor.
	 *
	 * @param Soap\Client      $soapApiClient   Soap client API.
	 * @param Log\ILogger      $logger          Logger.
	 * @param Repository       $orderRepository Order repository.
	 * @param Request          $request         Request.
	 * @param Options\Provider $optionsProvider Options provider.
	 * @param MessageManager   $messageManager  Message manager.
	 */
	public function __construct(
		Soap\Client $soapApiClient,
		Log\ILogger $logger,
		Repository $orderRepository,
		Request $request,
		Options\Provider $optionsProvider,
		MessageManager $messageManager
	) {
		$this->soapApiClient   = $soapApiClient;
		$this->logger          = $logger;
		$this->orderRepository = $orderRepository;
		$this->request         = $request;
		$this->optionsProvider = $optionsProvider;
		$this->messageManager  = $messageManager;
	}

	/**
	 * Process actions.
	 *
	 * @return void
	 */
	public function processActions(): void {
		$action = $this->request->getQuery( Plugin::PARAM_PACKETERY_ACTION );
		if ( self::ACTION_CANCEL_PACKET !== $action ) {
			return;
		}

		$redirectTo = $this->request->getQuery( self::PARAM_REDIRECT_TO );
		$order      = $this->orderRepository->getById( $this->getOrderId() );
		if ( null === $order ) {
			$record         = new Log\Record();
			$record->action = Log\Record::ACTION_PACKET_CANCEL;
			$record->status = Log\Record::STATUS_ERROR;
			$record->title  = __( 'Packet cancel error', 'packetery' );
			$record->params = [
				'orderId'      => $this->getOrderId(),
				'referer'      => (string) $this->request->getReferer(),
				'errorMessage' => 'Order not found',
			];

			$this->logger->add( $record );

			$this->messageManager->flash_message( __( 'Order not found', 'packetery' ), MessageManager::TYPE_ERROR );
			$this->redirectTo( $redirectTo, $order );
			return;
		}

		$this->cancelPacket( $order );
		$this->redirectTo( $redirectTo, $order );
	}

	/**
	 * Redirects.
	 *
	 * @param string            $redirectTo Redirect to.
	 * @param Entity\Order|null $order      Order.
	 *
	 * @return void
	 */
	private function redirectTo( string $redirectTo, ?Entity\Order $order ): void {
		if ( self::REDIRECT_TO_ORDER_GRID === $redirectTo ) {
			$packetCancelLink = add_query_arg(
				[
					'post_type' => 'shop_order',
				],
				admin_url( 'edit.php' )
			);

			if ( wp_safe_redirect( $packetCancelLink ) ) {
				exit;
			}
		}

		if ( self::REDIRECT_TO_ORDER_DETAIL === $redirectTo ) {
			$packetCancelLink = add_query_arg(
				[
					'post_type' => 'shop_order',
					'post'      => $order->getNumber(),
					'action'    => 'edit',
				],
				admin_url( 'post.php' )
			);

			if ( wp_safe_redirect( $packetCancelLink ) ) {
				exit;
			}
		}
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
			$record         = new Log\Record();
			$record->action = Log\Record::ACTION_PACKET_CANCEL;
			$record->status = Log\Record::STATUS_ERROR;
			$record->title  = __( 'Packet cancel error', 'packetery' );
			$record->params = [
				'orderId'      => $order->getNumber(),
				'packetId'     => $order->getPacketId(),
				'referer'      => (string) $this->request->getReferer(),
				'errorMessage' => 'Packet could not be cancelled',
			];

			$this->logger->add( $record );

			$this->messageManager->flash_message( __( 'Packet could not be cancelled', 'packetery' ), MessageManager::TYPE_ERROR );
			return;
		}

		$request = new Soap\Request\CancelPacket( (int) $order->getPacketId() );
		$result  = $this->soapApiClient->cancelPacket( $request );

		if ( ! $result->hasFault() ) {
			$record         = new Log\Record();
			$record->action = Log\Record::ACTION_PACKET_CANCEL;
			$record->status = Log\Record::STATUS_SUCCESS;
			$record->title  = __( 'Packet cancel success', 'packetery' );
			$record->params = [
				'orderId'  => $order->getNumber(),
				'packetId' => $order->getPacketId(),
			];

			$this->logger->add( $record );
		}

		if ( $result->hasFault() ) {
			$record         = new Log\Record();
			$record->action = Log\Record::ACTION_PACKET_CANCEL;
			$record->status = Log\Record::STATUS_ERROR;
			$record->title  = __( 'Packet cancel error', 'packetery' );
			$record->params = [
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
				$this->messageManager->flash_message( __( 'Packet could not be canceled in the Packeta system, packet was canceled only in the order list.', 'packetery' ), MessageManager::TYPE_SUCCESS );
			}

			if ( ! $result->hasFault() ) {
				$this->messageManager->flash_message( __( 'Packet has been successfully canceled both in the order list and the Packeta system.', 'packetery' ), MessageManager::TYPE_SUCCESS );
			}
		}

		if ( ! $this->shouldRevertSubmission( $result ) ) {
			$this->messageManager->flash_message( __( 'Failed to cancel packet. See Packeta log for more details.', 'packetery' ), MessageManager::TYPE_ERROR );
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

		$revertSubmition = ! $result->hasFault();

		if ( $result->hasCancelNotAllowedFault() && $this->optionsProvider->forcePacketCancel() ) {
			$revertSubmition = true;
		}

		return $revertSubmition;
	}

	/**
	 * Gets order ID.
	 *
	 * @return int|null
	 */
	public function getOrderId(): ?int {
		$orderId = $this->request->getQuery( self::PARAM_ORDER_ID );
		if ( is_numeric( $orderId ) ) {
			return (int) $orderId;
		}

		return null;
	}
}
