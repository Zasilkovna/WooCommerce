<?php

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Api\Soap;
use Packetery\Core\CoreHelper;
use Packetery\Core\Log;
use Packetery\Module;
use Packetery\Module\MessageManager;
use Packetery\Module\ModuleHelper;
use Packetery\Nette\Http\Request;

class PacketClaimSubmitter {

	/**
	 * @var Soap\Client
	 */
	private $soapApiClient;

	/**
	 * @var Log\ILogger
	 */
	private $logger;

	/**
	 * @var Repository
	 */
	private $orderRepository;

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

	/**
	 * @var CoreHelper
	 */
	private $coreHelper;

	public function __construct(
		Soap\Client $soapApiClient,
		Log\ILogger $logger,
		Repository $orderRepository,
		Request $request,
		MessageManager $messageManager,
		Module\Log\Page $logPage,
		PacketActionsCommonLogic $commonLogic,
		ModuleHelper $moduleHelper,
		CoreHelper $coreHelper
	) {
		$this->soapApiClient   = $soapApiClient;
		$this->logger          = $logger;
		$this->orderRepository = $orderRepository;
		$this->request         = $request;
		$this->messageManager  = $messageManager;
		$this->logPage         = $logPage;
		$this->commonLogic     = $commonLogic;
		$this->moduleHelper    = $moduleHelper;
		$this->coreHelper      = $coreHelper;
	}

	/**
	 * Process action
	 *
	 * @return void
	 */
	public function processAction(): void {
		$order      = $this->commonLogic->getOrder();
		$redirectTo = $this->request->getQuery( PacketActionsCommonLogic::PARAM_REDIRECT_TO );

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

		$request  = new Soap\Request\CreatePacketClaimWithPassword( $order );
		$response = $this->soapApiClient->createPacketClaimWithPassword( $request );
		if ( $response->hasFault() ) {
			$record->status = Log\Record::STATUS_ERROR;
			$record->title  = __( 'Packet claim could not be created.', 'packeta' );
			$record->params = [
				'request'      => $request->getSubmittableData(),
				'errorMessage' => $response->getFaultString(),
				'errors'       => $response->getValidationErrors(),
			];

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
		} else {
			$record->status = Log\Record::STATUS_SUCCESS;
			$record->title  = __( 'Packet claim was successfully created.', 'packeta' );
			$record->params = [
				'request'  => $request->getSubmittableData(),
				'packetId' => $response->getId(),
			];

			$order->setPacketClaimId( $response->getId() );
			$order->setPacketClaimTrackingUrl( $this->coreHelper->getTrackingUrl( $response->getId() ) );
			$order->setPacketClaimPassword( $response->getPassword() );
			$this->orderRepository->save( $order );

			$wcOrder = $this->orderRepository->getWcOrderById( (int) $order->getNumber() );
			if ( $wcOrder !== null ) {
				$wcOrder->add_order_note(
					sprintf(
						// translators: %s represents a packet tracking link.
						__( 'Packeta: Packet claim %s has been created', 'packeta' ),
						$this->moduleHelper->createHtmlLink( $order->getPacketClaimTrackingUrl(), $order->getPacketClaimBarcode() )
					)
				);
				$wcOrder->save();
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
		}

		$this->logger->add( $record );

		$redirectTo = $this->request->getQuery( PacketActionsCommonLogic::PARAM_REDIRECT_TO );
		$this->commonLogic->redirectTo( $redirectTo, $order );
	}
}
