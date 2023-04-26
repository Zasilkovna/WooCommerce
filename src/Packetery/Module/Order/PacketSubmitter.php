<?php
/**
 * Class PacketSubmitter
 *
 * @package Packetery\Module\Order
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Api\InvalidRequestException;
use Packetery\Core\Api\Soap;
use Packetery\Core\Entity;
use Packetery\Core\Log;
use Packetery\Core\Rounder;
use Packetery\Core\Validator;
use Packetery\Core\Api\Soap\CreatePacketMapper;
use Packetery\Module\Carrier\Options;
use Packetery\Module\MessageManager;
use Packetery\Module\ShippingMethod;
use PacketeryNette\Http\Request;
use WC_Order;
use Packetery\Module;

/**
 * Class PacketSubmitter
 *
 * @package Packetery\Module\Order
 */
class PacketSubmitter {

	/**
	 * SOAP API Client.
	 *
	 * @var Soap\Client SOAP API Client.
	 */
	private $soapApiClient;

	/**
	 * Order validator.
	 *
	 * @var Validator\Order
	 */
	private $orderValidator;

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
	 * CreatePacketMapper.
	 *
	 * @var CreatePacketMapper
	 */
	private $createPacketMapper;

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
	 * Options provider.
	 *
	 * @var Module\Options\Provider
	 */
	private $optionsProvider;

	/**
	 * OrderApi constructor.
	 *
	 * @param Soap\Client              $soapApiClient      SOAP API Client.
	 * @param Validator\Order          $orderValidator     Order validator.
	 * @param Log\ILogger              $logger             Logger.
	 * @param Repository               $orderRepository    Order repository.
	 * @param CreatePacketMapper       $createPacketMapper CreatePacketMapper.
	 * @param Request                  $request            Request.
	 * @param MessageManager           $messageManager     Message manager.
	 * @param Module\Log\Page          $logPage            Log page.
	 * @param PacketActionsCommonLogic $commonLogic        Common logic.
	 * @param Module\Options\Provider  $optionsProvider    Options provider.
	 */
	public function __construct(
		Soap\Client $soapApiClient,
		Validator\Order $orderValidator,
		Log\ILogger $logger,
		Repository $orderRepository,
		CreatePacketMapper $createPacketMapper,
		Request $request,
		MessageManager $messageManager,
		Module\Log\Page $logPage,
		PacketActionsCommonLogic $commonLogic,
		Module\Options\Provider $optionsProvider
	) {
		$this->soapApiClient      = $soapApiClient;
		$this->orderValidator     = $orderValidator;
		$this->logger             = $logger;
		$this->orderRepository    = $orderRepository;
		$this->createPacketMapper = $createPacketMapper;
		$this->request            = $request;
		$this->messageManager     = $messageManager;
		$this->logPage            = $logPage;
		$this->commonLogic        = $commonLogic;
		$this->optionsProvider    = $optionsProvider;
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
			$record->action  = Log\Record::ACTION_PACKET_SENDING;
			$record->status  = Log\Record::STATUS_ERROR;
			$record->orderId = null;
			$record->title   = __( 'Packet submission error', 'packeta' );
			$record->params  = [
				'referer'      => (string) $this->request->getReferer(),
				'errorMessage' => 'Order not found',
			];

			$this->logger->add( $record );

			$this->messageManager->flash_message( __( 'Order not found', 'packeta' ), MessageManager::TYPE_ERROR );
			$this->commonLogic->redirectTo( $redirectTo, $order );

			return;
		}

		$this->commonLogic->checkAction( PacketActionsCommonLogic::ACTION_SUBMIT_PACKET, $order );

		$submissionResult         = $this->submitPacket(
			$this->orderRepository->getWcOrderById( (int) $order->getNumber() ),
			$this->optionsProvider->isOrderStatusAutoChangeEnabled(),
			$order
		);
		$resultsCounter           = $submissionResult->getCounter();
		$submissionResultMessages = $this->getTranslatedSubmissionMessages( $resultsCounter, (int) $order->getNumber() );

		if ( $resultsCounter['success'] > 0 ) {
			$this->messageManager->flashMessageObject(
				Module\Message::create()
					->setText( $submissionResultMessages['success'] )
					->setEscape( false )
			);
		}

		if ( $resultsCounter['ignored'] > 0 ) {
			$this->messageManager->flashMessageObject(
				Module\Message::create()
					->setText( $submissionResultMessages['ignored'] )
					->setEscape( false )
					->setType( MessageManager::TYPE_INFO )
			);
		}

		if ( $resultsCounter['errors'] > 0 ) {
			$this->messageManager->flashMessageObject(
				Module\Message::create()
					->setText( $submissionResultMessages['errors'] )
					->setEscape( false )
					->setType( MessageManager::TYPE_ERROR )
			);
		}

		$redirectTo = $this->request->getQuery( PacketActionsCommonLogic::PARAM_REDIRECT_TO );
		$this->commonLogic->redirectTo( $redirectTo, $order );
	}

	/**
	 * Submits packet data to Packeta API.
	 *
	 * @param WC_Order          $wcOrder           WC order.
	 * @param bool              $updateOrderStatus Updates WC order status if packet was successfully created. Value is based on plugin settings.
	 * @param Entity\Order|null $order             Order.
	 *
	 * @return PacketSubmissionResult
	 */
	public function submitPacket(
		WC_Order $wcOrder,
		bool $updateOrderStatus,
		?Entity\Order $order = null
	): PacketSubmissionResult {
		$submissionResult = new PacketSubmissionResult();
		if ( null === $order ) {
			$order = $this->orderRepository->getByWcOrder( $wcOrder );
		}
		if ( null === $order ) {
			$submissionResult->increaseIgnoredCount();

			return $submissionResult;
		}

		$orderData       = $wcOrder->get_data();
		$shippingMethods = $wcOrder->get_shipping_methods();
		$shippingMethod  = reset( $shippingMethods );

		$shippingMethodData = $shippingMethod->get_data();
		$shippingMethodId   = $shippingMethodData['method_id'];
		if ( ShippingMethod::PACKETERY_METHOD_ID === $shippingMethodId && ! $order->isExported() ) {
			try {
				$createPacketData = $this->preparePacketData( $order );
			} catch ( InvalidRequestException $e ) {
				$record          = new Log\Record();
				$record->action  = Log\Record::ACTION_PACKET_SENDING;
				$record->status  = Log\Record::STATUS_ERROR;
				$record->title   = __( 'Packet could not be created.', 'packeta' );
				$record->params  = [
					'orderId'      => $orderData['id'],
					'errorMessage' => $e->getMessage(),
				];
				$record->orderId = $order->getNumber();
				$this->logger->add( $record );

				$submissionResult->increaseLogsCount();
				$submissionResult->increaseErrorsCount();

				return $submissionResult;
			}

			$response = $this->soapApiClient->createPacket( $createPacketData );
			if ( $response->hasFault() ) {
				$record          = new Log\Record();
				$record->action  = Log\Record::ACTION_PACKET_SENDING;
				$record->status  = Log\Record::STATUS_ERROR;
				$record->title   = __( 'Packet could not be created.', 'packeta' );
				$record->params  = [
					'request'      => $createPacketData,
					'errorMessage' => $response->getErrorsAsString(),
				];
				$record->orderId = $order->getNumber();
				$errorMessage    = $response->getErrorsAsString( false );

				$submissionResult->increaseErrorsCount();
			} else {
				$order->setIsExported( true );
				$order->setPacketId( (string) $response->getId() );

				$record          = new Log\Record();
				$record->action  = Log\Record::ACTION_PACKET_SENDING;
				$record->status  = Log\Record::STATUS_SUCCESS;
				$record->title   = __( 'Packet was successfully created.', 'packeta' );
				$record->params  = [
					'request'  => $createPacketData,
					'packetId' => $response->getId(),
				];
				$record->orderId = $order->getNumber();
				$errorMessage    = null;

				$submissionResult->increaseSuccessCount();
			}

			$submissionResult->increaseLogsCount();
			$this->logger->add( $record );
			$order->updateApiErrorMessage( $errorMessage );
			$this->orderRepository->save( $order );

			if ( $updateOrderStatus && false === $response->hasFault() ) {
				$this->updateOrderStatusOrLogError( $wcOrder, $order->getNumber(), $submissionResult );
			}
		} else {
			$submissionResult->increaseIgnoredCount();
		}

		return $submissionResult;
	}

	/**
	 * Prepares packet attributes.
	 *
	 * @param Entity\Order $order Order entity.
	 *
	 * @return array
	 * @throws InvalidRequestException For the case request is not eligible to be sent to API.
	 */
	private function preparePacketData( Entity\Order $order ): array {
		if ( ! $this->orderValidator->validate( $order ) ) {
			throw new InvalidRequestException( 'All required order attributes are not set.' );
		}

		$createPacketData = $this->createPacketMapper->fromOrderToArray( $order );
		if ( ! empty( $createPacketData['cod'] ) ) {
			if ( $order->getCarrierCode() === null ) {
				// This means that more accurate carrier id could not be determined. See Order\Builder.
				$roundingType = Rounder::DONT_ROUND;
			} else {
				$roundingType = Options::createByCarrierId( $order->getCarrierCode() )->getCodRoundingType();
			}

			$roundedCod              = Rounder::roundByCurrency( $createPacketData['cod'], $createPacketData['currency'], $roundingType );
			$createPacketData['cod'] = $roundedCod;
		}

		/**
		 * Allows to update CreatePacket request data.
		 *
		 * @since 1.4
		 *
		 * @param array $createPacketData CreatePacket request data.
		 */
		return (array) apply_filters( 'packeta_create_packet', $createPacketData );
	}

	/**
	 * Gets translated messages by submission result.
	 *
	 * @param array    $submissionResult Submission result.
	 * @param int|null $orderId Order ID.
	 *
	 * @return array
	 */
	public function getTranslatedSubmissionMessages( array $submissionResult, ?int $orderId ): array {
		$success = null;
		if ( is_numeric( $submissionResult['success'] ) && $submissionResult['success'] > 0 ) {
			if ( $submissionResult['logs'] > 0 ) {
				$success = sprintf( // translators: 1: link start 2: link end.
					esc_html__( 'Shipments were submitted successfully. %1$sShow logs%2$s', 'packeta' ),
					'<a href="' . $this->logPage->createLogListUrl( $orderId ) . '">',
					'</a>'
				);
			} else {
				$success = esc_html__( 'Shipments were submitted successfully.', 'packeta' );
			}
		}
		$ignored = null;
		if ( is_numeric( $submissionResult['ignored'] ) && $submissionResult['ignored'] > 0 ) {
			if ( $submissionResult['logs'] > 0 ) {
				$ignored = sprintf( // translators: 1: total number of shipments 2: link start 3: link end.
					esc_html__( 'Some shipments (%1$s in total) were not submitted (these were submitted already or are not Packeta orders). %2$sShow logs%3$s', 'packeta' ),
					$submissionResult['ignored'],
					'<a href="' . $this->logPage->createLogListUrl( $orderId ) . '">',
					'</a>'
				);
			} else {
				$ignored = sprintf( // translators: %s is count.
					esc_html__( 'Some shipments (%s in total) were not submitted (these were submitted already or are not Packeta orders).', 'packeta' ),
					$submissionResult['ignored']
				);
			}
		}
		$errors = null;
		if ( is_numeric( $submissionResult['errors'] ) && $submissionResult['errors'] > 0 ) {
			if ( $submissionResult['logs'] > 0 ) {
				$errors = sprintf( // translators: 1: total number of shipments 2: link start 3: link end.
					esc_html__( 'Some shipments (%1$s in total) failed to be submitted to Packeta. %2$sShow logs%3$s', 'packeta' ),
					$submissionResult['errors'],
					'<a href="' . $this->logPage->createLogListUrl( $orderId ) . '">',
					'</a>'
				);
			} else {
				$errors = sprintf( // translators: %s is count.
					esc_html__( 'Some shipments (%s in total) failed to be submitted to Packeta.', 'packeta' ),
					$submissionResult['errors']
				);
			}
		} elseif ( isset( $submissionResult['errors'] ) ) {
			$errors = esc_html( $submissionResult['errors'] );
		}

		if ( is_numeric( $submissionResult['statusUnchanged'] ) && $submissionResult['statusUnchanged'] > 0 ) {
			$errors = esc_html__( 'Some order statuses have not been automatically changed.', 'packeta' );
		}

		return [
			'success' => $success,
			'ignored' => $ignored,
			'errors'  => $errors,
		];
	}

	/**
	 * Updates order status or logs error.
	 *
	 * @param WC_Order               $order WC Order.
	 * @param string                 $orderId  Order ID.
	 * @param PacketSubmissionResult $submissionResult Packet submission result.
	 *
	 * @return void
	 */
	private function updateOrderStatusOrLogError( WC_Order $order, string $orderId, PacketSubmissionResult $submissionResult ):void {
		$autoOrderStatus = $this->optionsProvider->getValidAutoOrderStatus();
		if ( '' === $autoOrderStatus ) {
			$record         = new Log\Record();
			$record->action = Log\Record::ACTION_PACKET_SENDING;
			$record->status = Log\Record::STATUS_ERROR;

			$record->title = sprintf(
			// translators: %s represents unknown order status.
				__( 'Order status has not been changed, status "%s" doesn\'t exist.', 'packeta' ),
				$this->optionsProvider->getAutoOrderStatus()
			);
			$record->orderId = $orderId;
			$this->logger->add( $record );
			$submissionResult->increaseStatusUnchangedCount();
		} else {
			$order->update_status( $autoOrderStatus );
		}
	}

}
