<?php
/**
 * Class PacketSubmitter
 *
 * @package Packetery\Api
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Api\InvalidRequestException;
use Packetery\Core\Api\Soap;
use Packetery\Core\Api\Soap\Request\CreatePacket;
use Packetery\Core\Entity;
use Packetery\Core\Log;
use Packetery\Core\Rounder;
use Packetery\Core\Validator;
use Packetery\Module\Carrier\Options;
use Packetery\Module\MessageManager;
use Packetery\Module\Plugin;
use Packetery\Module\ShippingMethod;
use PacketeryNette\Http\Request;
use WC_Order;

/**
 * Class PacketSubmitter
 *
 * @package Packetery\Api
 */
class PacketSubmitter extends PacketActionsBase {

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
	 * Order validator.
	 *
	 * @var Validator\Order
	 */
	private $orderValidator;

	/**
	 * OrderApi constructor.
	 *
	 * @param Soap\Client     $soapApiClient   SOAP API Client.
	 * @param Repository      $orderRepository Order repository.
	 * @param Log\ILogger     $logger          Logger.
	 * @param Request         $request         Request.
	 * @param MessageManager  $messageManager  Message manager.
	 * @param Validator\Order $orderValidator  Order validator.
	 */
	public function __construct(
		Soap\Client $soapApiClient,
		Repository $orderRepository,
		Log\ILogger $logger,
		Request $request,
		MessageManager $messageManager,
		Validator\Order $orderValidator
	) {
		$this->orderValidator = $orderValidator;
		parent::__construct( $soapApiClient, $orderRepository, $logger, $request, $messageManager );
	}

	/**
	 * Process action
	 *
	 * @return void
	 */
	public function processAction(): void {
		$this->setAction( self::ACTION_SUBMIT_PACKET );
		$this->setLogAction( Log\Record::ACTION_PACKET_CANCEL );
		parent::processAction();
		$resultsCounter = [
			'success' => 0,
			'ignored' => 0,
			'errors'  => 0,
			'logs'    => 0,
		];
		$this->submitPacket( new WC_Order( $this->orderId ), $resultsCounter );

		if ( 1 === count( $resultsCounter['success'] ) ) {
			$this->messageManager->flash_message( __( 'Packet was sucessfully created.', 'packeta' ) );
		} elseif ( 1 === count( $resultsCounter['errors'] ) ) {
			$this->messageManager->flash_message( __( 'Packet could not be created.', 'packeta' ), MessageManager::TYPE_ERROR );
		}

		$redirectTo = $this->request->getQuery( self::PARAM_REDIRECT_TO );
		$this->redirectTo( $redirectTo, $this->order );
	}

	/**
	 * Submits packet data to Packeta API.
	 *
	 * @param WC_Order $order WC order.
	 * @param array    $resultsCounter Array with results.
	 */
	public function submitPacket( WC_Order $order, array &$resultsCounter ): void {
		$commonEntity = $this->orderRepository->getByWcOrder( $order );
		if ( null === $commonEntity ) {
			$resultsCounter['ignored'] ++;
			return;
		}

		$orderData       = $order->get_data();
		$shippingMethods = $order->get_shipping_methods();
		$shippingMethod  = reset( $shippingMethods );

		$shippingMethodData = $shippingMethod->get_data();
		$shippingMethodId   = $shippingMethodData['method_id'];
		if ( ShippingMethod::PACKETERY_METHOD_ID === $shippingMethodId && ! $commonEntity->isExported() ) {
			try {
				$createPacketRequest = $this->preparePacketRequest( $commonEntity );
			} catch ( InvalidRequestException $e ) {
				$record          = new Log\Record();
				$record->action  = Log\Record::ACTION_PACKET_SENDING;
				$record->status  = Log\Record::STATUS_ERROR;
				$record->title   = __( 'Packet could not be created.', 'packeta' );
				$record->params  = [
					'orderId'      => $orderData['id'],
					'errorMessage' => $e->getMessage(),
				];
				$record->orderId = $commonEntity->getNumber();
				$this->logger->add( $record );

				$resultsCounter['logs'] ++;
				$resultsCounter['errors'] ++;

				return;
			}

			$response = $this->soapApiClient->createPacket( $createPacketRequest );
			if ( $response->hasFault() ) {
				$record          = new Log\Record();
				$record->action  = Log\Record::ACTION_PACKET_SENDING;
				$record->status  = Log\Record::STATUS_ERROR;
				$record->title   = __( 'Packet could not be created.', 'packeta' );
				$record->params  = [
					'request'      => $createPacketRequest->getSubmittableData(),
					'errorMessage' => $response->getErrorsAsString(),
				];
				$record->orderId = $commonEntity->getNumber();
				$this->logger->add( $record );

				$resultsCounter['logs'] ++;
				$resultsCounter['errors'] ++;
			} else {
				$commonEntity->setIsExported( true );
				$commonEntity->setPacketId( (string) $response->getId() );
				$this->orderRepository->save( $commonEntity );

				$resultsCounter['success'] ++;

				$record          = new Log\Record();
				$record->action  = Log\Record::ACTION_PACKET_SENDING;
				$record->status  = Log\Record::STATUS_SUCCESS;
				$record->title   = __( 'Packet was sucessfully created.', 'packeta' );
				$record->params  = [
					'request'  => $createPacketRequest->getSubmittableData(),
					'packetId' => $response->getId(),
				];
				$record->orderId = $commonEntity->getNumber();
				$this->logger->add( $record );

				$resultsCounter['logs'] ++;
			}
		} else {
			$resultsCounter['ignored'] ++;
		}
	}

	/**
	 * Prepares packet attributes.
	 *
	 * @param Entity\Order $order Order entity.
	 *
	 * @return CreatePacket
	 * @throws InvalidRequestException For the case request is not eligible to be sent to API.
	 */
	private function preparePacketRequest( Entity\Order $order ): CreatePacket {
		/*
		TODO: extend validator to return specific errors.
		if ( ! $this->orderValidator->validate( $commonEntity ) ) {
			throw new InvalidRequestException( 'All required order attributes are not set.' );
		}
		*/

		$clonedOrder = clone($order);
		if ( $clonedOrder->hasCod() ) {
			$roundingType = Options::createByCarrierId( $clonedOrder->getCarrierCode() )->getCodRoundingType();
			$roundedCod   = Rounder::roundByCurrency( $clonedOrder->getCod(), $clonedOrder->getCurrency(), $roundingType );
			$clonedOrder->setCod( $roundedCod );
		}

		return new CreatePacket(
			/**
			 * Filters the input order for CreatePacket request.
			 *
			 * @since 1.4
			 *
			 * @param Entity\Order $order Packeta core order. DO NOT USE THIS STRICT TYPE IN YOUR METHOD SIGNATURE!
			 */
			apply_filters(
				'packeta_create_packet',
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
				unserialize(
					// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
					serialize( $clonedOrder )
				)
			)
		);
	}

}
