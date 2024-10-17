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
use Packetery\Module\ModuleHelper;
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
	 * ModuleHelper.
	 *
	 * @var ModuleHelper
	 */
	private $moduleHelper;

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
	 * @param ModuleHelper             $moduleHelper          ModuleHelper.
	 */
	public function __construct(
		Soap\Client $soapApiClient,
		Log\ILogger $logger,
		Repository $orderRepository,
		Request $request,
		MessageManager $messageManager,
		Module\Log\Page $logPage,
		PacketActionsCommonLogic $commonLogic,
		ModuleHelper $moduleHelper
	) {
		$this->soapApiClient   = $soapApiClient;
		$this->logger          = $logger;
		$this->orderRepository = $orderRepository;
		$this->request         = $request;
		$this->messageManager  = $messageManager;
		$this->logPage         = $logPage;
		$this->commonLogic     = $commonLogic;
		$this->moduleHelper    = $moduleHelper;
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
		if ( null === $order ) {
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
		if ( false === $order->isPacketClaimCreationPossible() ) {
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
			$order->setPacketClaimPassword( $response->getPassword() );
			$this->orderRepository->save( $order );

			$wcOrder = $this->orderRepository->getWcOrderById( (int) $order->getNumber() );
			if ( null !== $wcOrder ) {
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
