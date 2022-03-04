<?php
/**
 * Class PacketSubmitter
 *
 * @package Packetery\Api
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Api\InvalidRequestException;
use Packetery\Core\Api\Soap\Client;
use Packetery\Core\Api\Soap\Request\CreatePacket;
use Packetery\Core\Entity;
use Packetery\Core\Log;
use Packetery\Core\Validator;
use Packetery\Module\EntityFactory;
use Packetery\Module\Order as ModuleOrder;
use Packetery\Module\ShippingMethod;
use WC_Order;

/**
 * Class PacketSubmitter
 *
 * @package Packetery\Api
 */
class PacketSubmitter {

	/**
	 * SOAP API Client.
	 *
	 * @var Client SOAP API Client.
	 */
	private $soapApiClient;

	/**
	 * Order entity factory.
	 *
	 * @var EntityFactory\Order
	 */
	private $orderFactory;

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
	 * OrderApi constructor.
	 *
	 * @param Client              $soapApiClient   SOAP API Client.
	 * @param EntityFactory\Order $orderFactory    Order entity factory.
	 * @param Validator\Order     $orderValidator  Order validator.
	 * @param Log\ILogger         $logger          Logger.
	 * @param Repository          $orderRepository Order repository.
	 */
	public function __construct(
		Client $soapApiClient,
		EntityFactory\Order $orderFactory,
		Validator\Order $orderValidator,
		Log\ILogger $logger,
		Repository $orderRepository
	) {
		$this->soapApiClient   = $soapApiClient;
		$this->orderFactory    = $orderFactory;
		$this->orderValidator  = $orderValidator;
		$this->logger          = $logger;
		$this->orderRepository = $orderRepository;
	}

	/**
	 * Submits packet data to Packeta API.
	 *
	 * @param WC_Order $order WC order.
	 * @param array    $resultsCounter Array with results.
	 */
	public function submitPacket( WC_Order $order, array &$resultsCounter ): void {
		$commonEntity = $this->orderFactory->create( $order );
		if ( null === $commonEntity ) {
			$resultsCounter['ignored'] ++;

			return;
		}

		$orderData       = $order->get_data();
		$shippingMethods = $order->get_shipping_methods();
		$shippingMethod  = reset( $shippingMethods );

		$shippingMethodData = $shippingMethod->get_data();
		$shippingMethodId   = $shippingMethodData['method_id'];
		if ( ShippingMethod::PACKETERY_METHOD_ID === $shippingMethodId && null !== $commonEntity && ! $commonEntity->isExported() ) {
			try {
				$createPacketRequest = $this->preparePacketRequest( $commonEntity );
			} catch ( InvalidRequestException $e ) {
				$record         = new Log\Record();
				$record->action = Log\Record::ACTION_PACKET_SENDING;
				$record->status = Log\Record::STATUS_ERROR;
				$record->title  = __( 'packetSendingErrorLogTitle', 'packetery' );
				$record->params = [
					'orderId'      => $orderData['id'],
					'errorMessage' => $e->getMessage(),
				];
				$this->logger->add( $record );

				$resultsCounter['errors'] ++;

				return;
			}

			$response = $this->soapApiClient->createPacket( $createPacketRequest );
			if ( $response->hasFault() ) {
				$record         = new Log\Record();
				$record->action = Log\Record::ACTION_PACKET_SENDING;
				$record->status = Log\Record::STATUS_ERROR;
				$record->title  = __( 'packetSendingErrorLogTitle', 'packetery' );
				$record->params = [
					'request'      => $createPacketRequest->getSubmittableData(),
					'errorMessage' => $response->getErrorsAsString(),
				];
				$this->logger->add( $record );

				$resultsCounter['errors'] ++;
			} else {
				$this->orderRepository->update(
					[
						ModuleOrder\Entity::META_IS_EXPORTED => true,
						ModuleOrder\Entity::META_PACKET_ID => $response->getId(),
					],
					$orderData['id']
				);

				$resultsCounter['success'] ++;

				$record         = new Log\Record();
				$record->action = Log\Record::ACTION_PACKET_SENDING;
				$record->status = Log\Record::STATUS_SUCCESS;
				$record->title  = __( 'packetSendingSuccessLogTitle', 'packetery' );
				$record->params = [
					'packetId' => $response->getId(),
				];
				$this->logger->add( $record );
			}
		} else {
			$resultsCounter['ignored'] ++;
		}
	}

	/**
	 * Prepares packet attributes.
	 *
	 * @param Entity\Order $commonEntity Order entity.
	 *
	 * @return CreatePacket
	 * @throws InvalidRequestException For the case request is not eligible to be sent to API.
	 */
	private function preparePacketRequest( Entity\Order $commonEntity ): CreatePacket {
		/*
		TODO: extend validator to return specific errors.
		if ( ! $this->orderValidator->validate( $commonEntity ) ) {
			throw new InvalidRequestException( 'All required order attributes are not set.' );
		}
		*/

		return new CreatePacket( $commonEntity );
	}

}
