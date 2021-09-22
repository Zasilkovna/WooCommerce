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
use Packetery\Entity\Address;
use Packetery\Entity\Size;
use PacketeryModule\Carrier\Repository;
use PacketeryModule\Options\Provider;
use PacketeryModule\ShippingMethod;
use WC_Order;

/**
 * Class PacketSubmitter
 *
 * @package Packetery\Api
 */
class PacketSubmitter {
	/**
	 * Options provider.
	 *
	 * @var Provider Options provider.
	 */
	private $optionsProvider;

	/**
	 * Carrier repository.
	 *
	 * @var Repository Carrier repository.
	 */
	private $carrierRepository;

	/**
	 * SOAP API Client.
	 *
	 * @var Client SOAP API Client.
	 */
	private $soapApiClient;

	/**
	 * OrderApi constructor.
	 *
	 * @param Provider   $optionsProvider Options Provider.
	 * @param Repository $carrierRepository Carrier repository.
	 * @param Client     $soapApiClient SOAP API Client.
	 */
	public function __construct( Provider $optionsProvider, Repository $carrierRepository, Client $soapApiClient ) {
		$this->optionsProvider   = $optionsProvider;
		$this->carrierRepository = $carrierRepository;
		$this->soapApiClient     = $soapApiClient;
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
			try {
				$createPacketRequest = $this->preparePacketAttributes( $order );
			} catch ( IncompleteRequestException $e ) {
				// TODO: handle errors.
				$resultsCounter['ignored']++;

				return;
			}
			// TODO: update before release.
			$logger = wc_get_logger();
			if ( $logger ) {
				$logger->info( wp_json_encode( $createPacketRequest->getSubmittableData() ) );
			}

			$response = $this->soapApiClient->createPacket( $createPacketRequest );
			if ( $response->getFaultString() ) {
				// TODO: update before release.
				if ( $logger ) {
					$logger->error( $response->getErrorsAsString() );
				}
				$resultsCounter['errors']++;
			} else {
				update_post_meta( $orderData['id'], Entity::META_IS_EXPORTED, '1' );
				update_post_meta( $orderData['id'], Entity::META_PACKET_ID, $response->getBarcode() );
				$resultsCounter['success']++;
			}
		} else {
			$resultsCounter['ignored']++;
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
	private function preparePacketAttributes( WC_Order $order ): CreatePacket {
		$orderData          = $order->get_data();
		$contactInformation = ( $order->has_shipping_address() ? $orderData['shipping'] : $orderData['billing'] );
		$orderTotalPrice    = $order->get_total( 'raw' );
		$entity             = new Entity( $order );
		// Type cast of $orderTotalPrice is needed, PHPDoc is wrong.
		$request = new CreatePacket(
			(string) $orderData['id'],
			$contactInformation['first_name'],
			$contactInformation['last_name'],
			(float) $orderTotalPrice,
			$entity->getWeight(),
			$entity->getAddressId(),
			$this->optionsProvider->get_sender()
		);

		if ( $entity->isHomeDelivery() ) {
			$address = new Address( $contactInformation['address_1'], $contactInformation['city'], $contactInformation['postcode'] );
			$request->setAddress( $address );
			// Additional address information.
			if ( ! empty( $contactInformation['address_2'] ) ) {
				$request->setNote( $contactInformation['address_2'] );
			}
		}
		// Shipping address phone is optional.
		$request->setPhone( $orderData['billing']['phone'] );
		if ( ! empty( $contactInformation['phone'] ) ) {
			$request->setPhone( $contactInformation['phone'] );
		}

		$request->setEmail( $orderData['billing']['email'] );
		$codMethod = $this->optionsProvider->getCodPaymentMethod();
		if ( $orderData['payment_method'] === $codMethod ) {
			$request->setCod( $orderTotalPrice );
		}
		if ( $entity->isExternalPickupPointDelivery() ) {
			$pointCarrierId = $entity->getPointCarrierId();
			$request->setCarrierPickupPoint( $pointCarrierId );
		}
		if ( $entity->isExternalCarrier() ) {
			$carrierId = $entity->getCarrierId();
			$carrier   = $this->carrierRepository->getById( (int) $carrierId );
			if ( $carrier && $carrier->requiresSize() ) {
				if ( $entity->getLength() && $entity->getWidth() && $entity->getHeight() ) {
					$size = new Size( $entity->getLength(), $entity->getWidth(), $entity->getHeight() );
					$request->setSize( $size );
				} else {
					throw new IncompleteRequestException( 'All packet dimensions are not set.' );
				}
			}
		}

		return $request;
	}
}
