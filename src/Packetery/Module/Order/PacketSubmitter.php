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
use Packetery\Core\Helper;
use Packetery\Core\Log;
use Packetery\Core\Validator;
use Packetery\Module\Options\Provider;
use Packetery\Module\ShippingMethod;
use Packetery\Module\Carrier\Repository as CarrierRepository;
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
	 * Options provider.
	 *
	 * @var Provider
	 */
	private $optionsProvider;

	/**
	 * Carrier repository
	 *
	 * @var CarrierRepository
	 */
	private $carrierRepository;

	/**
	 * OrderApi constructor.
	 *
	 * @param Client            $soapApiClient   SOAP API Client.
	 * @param Validator\Order   $orderValidator  Order validator.
	 * @param Log\ILogger       $logger          Logger.
	 * @param Repository        $orderRepository Order repository.
	 * @param Provider          $optionsProvider Options provider.
	 * @param CarrierRepository $carrierRepository Carrier repository.
	 */
	public function __construct(
		Client $soapApiClient,
		Validator\Order $orderValidator,
		Log\ILogger $logger,
		Repository $orderRepository,
		Provider $optionsProvider,
		CarrierRepository $carrierRepository
	) {
		$this->soapApiClient     = $soapApiClient;
		$this->orderValidator    = $orderValidator;
		$this->logger            = $logger;
		$this->orderRepository   = $orderRepository;
		$this->optionsProvider   = $optionsProvider;
		$this->carrierRepository = $carrierRepository;
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

		if ( null !== $order->getCod() ) {
			$carrierId = $order->getCarrierId();
			if ( CarrierRepository::INTERNAL_PICKUP_POINTS_ID === $carrierId ) {
				$wcOrder = wc_get_order( $order->getNumber() );
				if ( $wcOrder instanceof WC_Order ) {
					$carrierId = $this->carrierRepository->getZpointCarrierIdByCountry( strtolower( $wcOrder->get_shipping_country() ) );
				}
			}

			$roundingType = $this->optionsProvider->getCarrierRoundingType( $carrierId );
			$order->setCod( Helper::customRoundByCurrency( $order->getCod(), $roundingType, $order->getCurrency() ) );

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
					serialize( $order )
				)
			)
		);
	}

}
