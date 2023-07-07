<?php
/**
 * Class PacketClaimSubmitter.
 *
 * @package Packetery
 */

declare( strict_types=1 );


namespace Packetery\Module\Order;

use Packetery\Core\Api\Soap;
use Packetery\Core\Log;
use Packetery\Module\MessageManager;
use Packetery\Nette\Http\Request;
use Packetery\Module;

/**
 * Class PacketClaimSubmitter.
 */
class PacketClaimSubmitter {

	/**
	 * SOAP API Client.
	 *
	 * @var Soap\Client SOAP API Client.
	 */
	private $soapApiClient;

	/**
	 * ILogger.
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
	 * Request.
	 *
	 * @var Request
	 */
	private $request;

	/**
	 * Message manager.
	 *
	 * @var MessageManager
	 */
	private $messageManager;

	/**
	 * Log page.
	 *
	 * @var Module\Log\Page
	 */
	private $logPage;

	/**
	 * Common logic.
	 *
	 * @var PacketActionsCommonLogic
	 */
	private $commonLogic;

	/**
	 * OrderApi constructor.
	 *
	 * @param Soap\Client              $soapApiClient   SOAP API Client.
	 * @param Log\ILogger              $logger          Logger.
	 * @param Repository               $orderRepository Order repository.
	 * @param Request                  $request         Request.
	 * @param MessageManager           $messageManager  Message manager.
	 * @param Module\Log\Page          $logPage         Log page.
	 * @param PacketActionsCommonLogic $commonLogic     Common logic.
	 */
	public function __construct(
		Soap\Client $soapApiClient,
		Log\ILogger $logger,
		Repository $orderRepository,
		Request $request,
		MessageManager $messageManager,
		Module\Log\Page $logPage,
		PacketActionsCommonLogic $commonLogic
	) {
		$this->soapApiClient   = $soapApiClient;
		$this->logger          = $logger;
		$this->orderRepository = $orderRepository;
		$this->request         = $request;
		$this->messageManager  = $messageManager;
		$this->logPage         = $logPage;
		$this->commonLogic     = $commonLogic;
	}

	/**
	 * Process action
	 *
	 * @return void
	 */
	public function processAction(): void {
		$order      = $this->commonLogic->getOrder();
		$redirectTo = $this->request->getQuery( PacketActionsCommonLogic::PARAM_REDIRECT_TO );

		if ( null === $order ) {
			$record          = new Log\Record();
			$record->action  = Log\Record::ACTION_PACKET_CLAIM_SENDING;
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

		$this->commonLogic->checkAction( PacketActionsCommonLogic::ACTION_CREATE_PACKET_CLAIM, $order );

		if ( false === $order->isPacketClaimCreationPossible() ) {
			$record          = new Log\Record();
			$record->action  = Log\Record::ACTION_PACKET_CLAIM_SENDING;
			$record->status  = Log\Record::STATUS_ERROR;
			$record->orderId = null;
			$record->title   = __( 'Packet claim submission error', 'packeta' );
			$record->params  = [
				'origin'       => (string) $this->request->getOrigin(),
				'errorMessage' => 'Packet claim creation is not possible',
			];

			$this->logger->add( $record );

			$this->messageManager->flash_message( __( 'Packet claim creation is not possible', 'packeta' ), MessageManager::TYPE_ERROR );
			$this->commonLogic->redirectTo( $redirectTo, $order );

			return;
		}

		$request  = new Soap\Request\CreatePacketClaimWithPassword( $order );
		$response = $this->soapApiClient->createPacketClaimWithPassword( $request );
		if ( $response->hasFault() ) {
			$record          = new Log\Record();
			$record->action  = Log\Record::ACTION_PACKET_CLAIM_SENDING;
			$record->status  = Log\Record::STATUS_ERROR;
			$record->title   = __( 'Packet claim could not be created.', 'packeta' );
			$record->params  = [
				'request'      => $request->getSubmittableData(),
				'errorMessage' => $response->getFaultString(),
				'errors'       => $response->getValidationErrors(),
			];
			$record->orderId = $order->getNumber();

			$faultFlashMessage = sprintf( // translators: 1: link start 2: link end.
				esc_html__( 'Packet claim could not be created. %1$sShow logs%2$s', 'packeta' ),
				...Module\Plugin::createLinkParts( $this->logPage->createLogListUrl( (int) $order->getNumber() ) )
			);

			$this->messageManager->flashMessageObject(
				Module\Message::create()
					->setType( MessageManager::TYPE_ERROR )
					->setText( $faultFlashMessage )
					->setEscape( false )
			);

		} else {
			$record          = new Log\Record();
			$record->action  = Log\Record::ACTION_PACKET_CLAIM_SENDING;
			$record->status  = Log\Record::STATUS_SUCCESS;
			$record->title   = __( 'Packet claim was successfully created.', 'packeta' );
			$record->params  = [
				'request'  => $request->getSubmittableData(),
				'packetId' => $response->getId(),
			];
			$record->orderId = $order->getNumber();

			$order->setPacketClaimId( $response->getId() );
			$order->setPacketClaimPassword( $response->getPassword() );
			$this->orderRepository->save( $order );

			$this->messageManager->flashMessageObject(
				Module\Message::create()
					->setText( __( 'Packet claim submitted', 'packeta' ) )
			);
		}

		$this->logger->add( $record );

		$redirectTo = $this->request->getQuery( PacketActionsCommonLogic::PARAM_REDIRECT_TO );
		$this->commonLogic->redirectTo( $redirectTo, $order );
	}
}
