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
use Packetery\Module\Carrier\CarrierOptionsFactory;
use Packetery\Module\CustomsDeclaration;
use Packetery\Module\Exception\InvalidCarrierException;
use Packetery\Module\MessageManager;
use Packetery\Module\ModuleHelper;
use Packetery\Module\ShippingMethod;
use Packetery\Nette\Http\Request;
use WC_Order;
use Packetery\Module;

/**
 * Class PacketSubmitter
 *
 * @package Packetery\Module\Order
 */
class PacketSubmitter {
	const HOOK_PACKET_STATUS_SYNC = 'packetery_packet_status_sync_hook';

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
	 * Customs declaration repository.
	 *
	 * @var CustomsDeclaration\Repository
	 */
	private $customsDeclarationRepository;

	/**
	 * Packet synchronizer.
	 *
	 * @var PacketSynchronizer
	 */
	private $packetSynchronizer;

	/**
	 * ModuleHelper.
	 *
	 * @var ModuleHelper
	 */
	private $moduleHelper;

	/**
	 * Carrier options factory.
	 *
	 * @var CarrierOptionsFactory
	 */
	private $carrierOptionsFactory;

	/**
	 * OrderApi constructor.
	 *
	 * @param Soap\Client                   $soapApiClient                SOAP API Client.
	 * @param Validator\Order               $orderValidator               Order validator.
	 * @param Log\ILogger                   $logger                       Logger.
	 * @param Repository                    $orderRepository              Order repository.
	 * @param CreatePacketMapper            $createPacketMapper           CreatePacketMapper.
	 * @param Request                       $request                      Request.
	 * @param MessageManager                $messageManager               Message manager.
	 * @param Module\Log\Page               $logPage                      Log page.
	 * @param PacketActionsCommonLogic      $commonLogic                  Common logic.
	 * @param CustomsDeclaration\Repository $customsDeclarationRepository Customs declaration repository.
	 * @param PacketSynchronizer            $packetSynchronizer           Packet synchronizer.
	 * @param ModuleHelper                  $moduleHelper                 ModuleHelper.
	 * @param CarrierOptionsFactory         $carrierOptionsFactory        Carrier options factory.
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
		CustomsDeclaration\Repository $customsDeclarationRepository,
		PacketSynchronizer $packetSynchronizer,
		ModuleHelper $moduleHelper,
		CarrierOptionsFactory $carrierOptionsFactory
	) {
		$this->soapApiClient                = $soapApiClient;
		$this->orderValidator               = $orderValidator;
		$this->logger                       = $logger;
		$this->orderRepository              = $orderRepository;
		$this->createPacketMapper           = $createPacketMapper;
		$this->request                      = $request;
		$this->messageManager               = $messageManager;
		$this->logPage                      = $logPage;
		$this->commonLogic                  = $commonLogic;
		$this->customsDeclarationRepository = $customsDeclarationRepository;
		$this->packetSynchronizer           = $packetSynchronizer;
		$this->moduleHelper                 = $moduleHelper;
		$this->carrierOptionsFactory        = $carrierOptionsFactory;
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
			$order,
			true
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
	 * @param WC_Order          $wcOrder                    WC order.
	 * @param Entity\Order|null $order                      Order.
	 * @param bool              $immediatePacketStatusCheck Whether to sync status immediately.
	 *
	 * @return PacketSubmissionResult
	 */
	public function submitPacket(
		WC_Order $wcOrder,
		?Entity\Order $order = null,
		bool $immediatePacketStatusCheck = false
	): PacketSubmissionResult {
		$submissionResult = new PacketSubmissionResult();
		if ( null === $order ) {
			try {
				$order = $this->orderRepository->getByWcOrder( $wcOrder );
			} catch ( InvalidCarrierException $exception ) {
				$order = null;
			}
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

			$customsDeclaration = $order->getCustomsDeclaration();
			if (
				null !== $customsDeclaration &&
				null === $customsDeclaration->getInvoiceFileId() &&
				$customsDeclaration->hasInvoiceFileContent()
			) {
				$invoiceFileResponse = $this->soapApiClient->createStorageFile(
					new Soap\Request\CreateStorageFile(
						// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
						base64_encode( $customsDeclaration->getInvoiceFile() ),
						sprintf( 'invoice_%s.pdf', $customsDeclaration->getId() )
					)
				);

				if ( $invoiceFileResponse->hasFault() ) {
					$record          = new Log\Record();
					$record->action  = Log\Record::ACTION_PACKET_SENDING;
					$record->status  = Log\Record::STATUS_ERROR;
					$record->title   = __( 'Packet invoice file could not be created.', 'packeta' );
					$record->params  = [
						'errorMessage' => $invoiceFileResponse->getFaultString(),
					];
					$record->orderId = $order->getNumber();
					$this->logger->add( $record );
					$submissionResult->increaseLogsCount();

					$submissionResult->increaseErrorsCount();
					$order->updateApiErrorMessage( $invoiceFileResponse->getFaultString() );
					$this->orderRepository->save( $order );

					return $submissionResult;
				}

				$customsDeclaration->setInvoiceFileId( $invoiceFileResponse->getId() );
				$this->customsDeclarationRepository->save( $customsDeclaration );
			}

			if (
				null !== $customsDeclaration &&
				null === $customsDeclaration->getEadFileId() &&
				$customsDeclaration->hasEadFileContent()
			) {
				$eadFileResponse = $this->soapApiClient->createStorageFile(
					new Soap\Request\CreateStorageFile(
						// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
						base64_encode( $customsDeclaration->getEadFile() ),
						sprintf( 'ead_%s.pdf', $customsDeclaration->getId() )
					)
				);

				if ( $eadFileResponse->hasFault() ) {
					$record          = new Log\Record();
					$record->action  = Log\Record::ACTION_PACKET_SENDING;
					$record->status  = Log\Record::STATUS_ERROR;
					$record->title   = __( 'Packet ead file could not be created.', 'packeta' );
					$record->params  = [
						'errorMessage' => $eadFileResponse->getFaultString(),
					];
					$record->orderId = $order->getNumber();
					$this->logger->add( $record );
					$submissionResult->increaseLogsCount();

					$submissionResult->increaseErrorsCount();
					$order->updateApiErrorMessage( $eadFileResponse->getFaultString() );
					$this->orderRepository->save( $order );

					return $submissionResult;
				}

				$customsDeclaration->setEadFileId( $eadFileResponse->getId() );
				$this->customsDeclarationRepository->save( $customsDeclaration );
			}

			try {
				$createPacketData = $this->preparePacketData( $order );
			} catch ( InvalidRequestException $e ) {
				$record          = new Log\Record();
				$record->action  = Log\Record::ACTION_PACKET_SENDING;
				$record->status  = Log\Record::STATUS_ERROR;
				$record->title   = __( 'Packet could not be created.', 'packeta' );
				$record->params  = [
					'orderId'       => $orderData['id'],
					'errorMessages' => $e->getMessages(),
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
				$order->setPacketId( $response->getId() );

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

				$wcOrder->add_order_note(
					sprintf(
						// translators: %s represents a packet tracking link.
						__( 'Packeta: Packet %s has been created', 'packeta' ),
						$this->moduleHelper->createHtmlLink( $order->getPacketTrackingUrl(), $order->getPacketBarcode() )
					)
				);
				$wcOrder->save();

				if ( $immediatePacketStatusCheck || ! function_exists( 'as_enqueue_async_action' ) ) {
					$this->packetSynchronizer->syncStatus( $order );
				} else {
					as_enqueue_async_action( self::HOOK_PACKET_STATUS_SYNC, [ $order->getNumber() ] );
				}
			}

			$submissionResult->increaseLogsCount();
			$this->logger->add( $record );
			$order->updateApiErrorMessage( $errorMessage );
			$this->orderRepository->save( $order );
		} else {
			$submissionResult->increaseIgnoredCount();
		}

		return $submissionResult;
	}

	/**
	 * Prepares packet attributes.
	 *
	 * @param Entity\Order $order Order entity.
	 * @return array
	 * @throws InvalidRequestException For the case request is not eligible to be sent to API.
	 */
	private function preparePacketData( Entity\Order $order ): array {
		$validationErrors = $this->orderValidator->validate( $order );
		if ( ! empty( $validationErrors ) ) {
			throw new InvalidRequestException( 'All required order attributes are not set.', $validationErrors );
		}

		$createPacketData = $this->createPacketMapper->fromOrderToArray( $order );
		if ( ! empty( $createPacketData['cod'] ) ) {
			$roundingType            = $this->carrierOptionsFactory->createByCarrierId( $order->getCarrier()->getId() )->getCodRoundingType();
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
	 * Registers action for cron.
	 *
	 * @return void
	 */
	public function registerCronAction(): void {
		add_action(
			self::HOOK_PACKET_STATUS_SYNC,
			function ( string $orderId ): void {
				$order = $this->orderRepository->getById( (int) $orderId, true );
				if ( null === $order ) {
					return;
				}
				$this->packetSynchronizer->syncStatus( $order );
			},
			10,
			1
		);
	}

}
