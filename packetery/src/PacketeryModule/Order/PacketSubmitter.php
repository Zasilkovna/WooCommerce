<?php
/**
 * Class PacketSubmitter
 *
 * @package Packetery\Api
 */

declare( strict_types=1 );

namespace PacketeryModule\Order;

use Packetery\Api\IncompleteRequestException;
use Packetery\Api\Soap\Client;
use Packetery\Api\Soap\Request\CreatePacket;
use Packetery\Validator;
use PacketeryModule\EntityFactory;
use PacketeryModule\ShippingMethod;
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
	 * OrderApi constructor.
	 *
	 * @param Client              $soapApiClient SOAP API Client.
	 * @param EntityFactory\Order $orderFactory Order entity factory.
	 * @param Validator\Order     $orderValidator Order validator.
	 */
	public function __construct(
		Client $soapApiClient,
		EntityFactory\Order $orderFactory,
		Validator\Order $orderValidator
	) {
		$this->soapApiClient  = $soapApiClient;
		$this->orderFactory   = $orderFactory;
		$this->orderValidator = $orderValidator;
	}

	/**
	 * Submits packet data to Packeta API.
	 *
	 * @param WC_Order $order WC order.
	 * @param array    $resultsCounter Array with results.
	 */
	public function submitPacket( WC_Order $order, array &$resultsCounter ): void {
		$entity          = new Entity( $order );
		$orderData       = $order->get_data();
		$shippingMethods = $order->get_shipping_methods();
		$shippingMethod  = reset( $shippingMethods );

		$shippingMethodData = $shippingMethod->get_data();
		$shippingMethodId   = $shippingMethodData['method_id'];
		if ( ShippingMethod::PACKETERY_METHOD_ID === $shippingMethodId && ! $entity->isExported() ) {
			// TODO: update logging before release, handle errors.
			$logger = wc_get_logger();
			try {
				$createPacketRequest = $this->preparePacketRequest( $order );
			} catch ( IncompleteRequestException $e ) {
				if ( $logger ) {
					$logger->info( $orderData['id'] . ': ' . $e->getMessage() );
				}
				$resultsCounter['errors'] ++;

				return;
			}
			if ( $logger ) {
				$logger->info( wp_json_encode( $createPacketRequest->getSubmittableData() ) );
			}

			$response = $this->soapApiClient->createPacket( $createPacketRequest );
			if ( $response->getFaultString() ) {
				if ( $logger ) {
					$logger->error( $response->getErrorsAsString() );
				}
				$resultsCounter['errors'] ++;
			} else {
				update_post_meta( $orderData['id'], Entity::META_IS_EXPORTED, '1' );
				update_post_meta( $orderData['id'], Entity::META_PACKET_ID, $response->getBarcode() );
				$resultsCounter['success'] ++;
			}
		} else {
			$resultsCounter['ignored'] ++;
		}
	}

	/**
	 * Prepares packet attributes.
	 *
	 * @param WC_Order $order WC order.
	 *
	 * @return CreatePacket
	 * @throws IncompleteRequestException For the case request is not eligible to be sent to API.
	 */
	private function preparePacketRequest( WC_Order $order ): CreatePacket {
		$commonEntity = $this->orderFactory->create( $order );
		if ( ! $this->orderValidator->validate( $commonEntity ) ) {
			throw new IncompleteRequestException( 'All required order attributes are not set.' );
		}

		return new CreatePacket( $commonEntity );
	}

}
