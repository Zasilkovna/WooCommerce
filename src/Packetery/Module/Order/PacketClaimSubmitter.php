<?php

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Entity\PacketReturn;
use Packetery\Core\Log;
use Packetery\Module;
use Packetery\Module\MessageManager;
use Packetery\Module\ModuleHelper;
use Packetery\Module\Returns\ReturnService;
use Packetery\Nette\Http\Request;

class PacketClaimSubmitter {

	/**
	 * @var Log\ILogger
	 */
	private $logger;

	/**
	 * @var Request
	 */
	private $request;

	/**
	 * @var MessageManager
	 */
	private $messageManager;

	/**
	 * @var Module\Log\Page
	 */
	private $logPage;

	/**
	 * @var PacketActionsCommonLogic
	 */
	private $commonLogic;

	/**
	 * @var ModuleHelper
	 */
	private $moduleHelper;
	private ReturnService $returnService;

	public function __construct(
		Log\ILogger $logger,
		Request $request,
		MessageManager $messageManager,
		Module\Log\Page $logPage,
		PacketActionsCommonLogic $commonLogic,
		ModuleHelper $moduleHelper,
		ReturnService $returnService
	) {
		$this->logger         = $logger;
		$this->request        = $request;
		$this->messageManager = $messageManager;
		$this->logPage        = $logPage;
		$this->commonLogic    = $commonLogic;
		$this->moduleHelper   = $moduleHelper;
		$this->returnService  = $returnService;
	}

	/**
	 * Process action
	 *
	 * @return void
	 */
	public function processAction(): void {
		$order         = $this->commonLogic->getOrder();
		$redirectToRaw = $this->request->getQuery( PacketActionsCommonLogic::PARAM_REDIRECT_TO );
		$redirectTo    = is_string( $redirectToRaw ) ? $redirectToRaw : '';

		$record         = new Log\Record();
		$record->action = Log\Record::ACTION_PACKET_CLAIM_SENDING;
		if ( $order === null ) {
			$record->status  = Log\Record::STATUS_ERROR;
			$record->orderId = null;
			$record->title   = __( 'Packet claim submission error', 'packeta' );
			$record->params  = [
				'origin'       => (string) $this->request->getOrigin(),
				'errorMessage' => 'Order not found',
			];

			$this->logger->add( $record );

			$this->messageManager->flash_message( __( 'Order not found', 'packeta' ), MessageManager::TYPE_ERROR );
			$this->commonLogic->redirectTo( $redirectTo, $order );

			return;
		}

		$this->commonLogic->checkAction( PacketActionsCommonLogic::ACTION_SUBMIT_PACKET_CLAIM, $order );

		$record->orderId = $order->getNumber();
		if ( $order->isPacketClaimCreationPossible() === false ) {
			$record->status = Log\Record::STATUS_ERROR;
			$record->title  = __( 'Packet claim submission error', 'packeta' );
			$record->params = [
				'origin'        => (string) $this->request->getOrigin(),
				'errorMessage'  => 'Packet claim creation is not possible',
				'packetStatus'  => $order->getPacketStatus(),
				'packetClaimId' => $order->getPacketClaimId(),
			];

			$this->logger->add( $record );

			$faultFlashMessage = sprintf( // translators: 1: link start 2: link end.
				esc_html__( 'Packet claim creation is not possible. %1$sShow logs%2$s', 'packeta' ),
				...$this->moduleHelper->createLinkParts( $this->logPage->createLogListUrl( (int) $order->getNumber() ) )
			);

			$this->messageManager->flashMessageObject(
				Module\Message::create()
					->setType( MessageManager::TYPE_ERROR )
					->setText( $faultFlashMessage )
					->setEscape( false )
			);
			$this->commonLogic->redirectTo( $redirectTo, $order );

			return;
		}

		$result = $this->returnService->createReturn( $order, PacketReturn::SOURCE_ADMIN );

		if ( $result->hasFault() ) {
			$faultFlashMessage = sprintf( // translators: 1: link start 2: link end.
				esc_html__( 'Packet claim could not be created. %1$sShow logs%2$s', 'packeta' ),
				...$this->moduleHelper->createLinkParts( $this->logPage->createLogListUrl( (int) $order->getNumber() ) )
			);

			$this->messageManager->flashMessageObject(
				Module\Message::create()
					->setType( MessageManager::TYPE_ERROR )
					->setText( $faultFlashMessage )
					->setEscape( false )
			);
			$this->commonLogic->redirectTo( $redirectTo, $order );

			return;
		}

		if ( $result->isOrderSaved() === false ) {
			$this->messageManager->flash_message(
				__( 'An error occurred while saving the order. More details in WC log.', 'packeta' ),
				MessageManager::TYPE_ERROR
			);
		}

		$flashMessage = sprintf( // translators: 1: link start 2: link end.
			esc_html__( 'Packet claim submitted. %1$sShow logs%2$s', 'packeta' ),
			...$this->moduleHelper->createLinkParts( $this->logPage->createLogListUrl( (int) $order->getNumber() ) )
		);

		$this->messageManager->flashMessageObject(
			Module\Message::create()
				->setText( $flashMessage )
				->setEscape( false )
		);

		$this->commonLogic->redirectTo( $redirectTo, $order );
	}
}
