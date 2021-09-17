<?php
/**
 * Class PacketSubmitter
 *
 * @package Packetery\Api
 */

declare( strict_types=1 );

namespace Packetery\Order;

use Packetery\Api\Soap\Client;
use Packetery\Api\Soap\Request\CreatePacket;
use Packetery\Carrier\Repository;
use Packetery\Options\Provider;
use Packetery\ShippingMethod;
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
	 * @param array    $results Array with results.
	 *
	 * @return array
	 */
	public function submitPacket( WC_Order $order, array &$results ): array {
		$entity          = new Entity( $order );
		$orderData       = $order->get_data();
		$shippingMethods = $order->get_shipping_methods();
		$shippingMethod  = reset( $shippingMethods );

		$shippingMethodData = $shippingMethod->get_data();
		$shippingMethodId   = $shippingMethodData['method_id'];
		if ( ShippingMethod::PACKETERY_METHOD_ID === $shippingMethodId && ! $entity->isExported() ) {
			$createPacketRequest = $this->preparePacketAttributes( $order, $orderData );
			// TODO: update before release.
			$logger = wc_get_logger();
			if ( $logger ) {
				$logger->info( wp_json_encode( $createPacketRequest ) );
			}

			$response = $this->soapApiClient->createPacket( $createPacketRequest );
			if ( $response->getErrors() ) {
				// TODO: update before release.
				if ( $logger ) {
					$logger->error( $response->getErrorsAsString() );
				}
				$results['error'][] = $response->getErrors();
			} else {
				update_post_meta( $orderData['id'], Entity::META_IS_EXPORTED, '1' );
				update_post_meta( $orderData['id'], Entity::META_PACKET_ID, $response->getBarcode() );
				$results['submitted'][] = $orderData['id'];
			}
		} else {
			$results['skipped'][] = $orderData['id'];
		}

		return $results;
	}

	/**
	 * Prepares packet attributes.
	 *
	 * @param WC_Order $order WC order.
	 * @param array    $orderData Order data.
	 *
	 * @return CreatePacket
	 */
	private function preparePacketAttributes( WC_Order $order, array $orderData ): CreatePacket {
		$entity          = new Entity( $order );
		$orderTotalPrice = $order->get_total( 'raw' );
		$codMethod       = $this->optionsProvider->getCodPaymentMethod();

		$checkForRequiredSize  = false;
		$pointId               = $entity->getPointId();
		$carrierId             = $entity->getCarrierId();
		$pointCarrierId        = $entity->getPointCarrierId();
		$isHomeDelivery        = ! empty( $carrierId ) && empty( $pointId );
		$isExternalPickupPoint = ! empty( $pointCarrierId );
		if ( $isExternalPickupPoint || $isHomeDelivery ) {
			// External pickup points or home delivery.
			$addressId            = $carrierId;
			$checkForRequiredSize = true;
		} else {
			// Internal pickup points.
			$addressId = $pointId;
		}

		$request = new CreatePacket();
		$request->setNumber( $orderData['id'] );
		$request->setEmail( $orderData['billing']['email'] );
		$request->setAddressId( (int) $addressId );
		$request->setValue( $orderTotalPrice );
		$request->setEshop( $this->optionsProvider->get_sender() );
		$request->setWeight( $entity->getWeight() );
		$this->prepareContactInfo( $order, $orderData, $request, $isHomeDelivery );

		if ( $orderData['payment_method'] === $codMethod ) {
			$request->setCod( $orderTotalPrice );
		}
		if ( $isExternalPickupPoint ) {
			$request->setCarrierPickupPoint( $pointCarrierId );
		}
		if ( true === $checkForRequiredSize ) {
			$carrier = $this->carrierRepository->getById( $carrierId );
			if ( $carrier && $carrier->requiresSize() ) {
				$request->setSize(
					$entity->getLength(),
					$entity->getWidth(),
					$entity->getHeight()
				);
			}
		}

		return $request;
	}

	/**
	 * Prepares and sets contact information.
	 *
	 * @param WC_Order     $order WC order.
	 * @param array        $orderData Order data.
	 * @param CreatePacket $request CreatePacket request.
	 * @param bool         $isHomeDelivery Home delivery flag.
	 */
	private function prepareContactInfo( WC_Order $order, array $orderData, CreatePacket $request, bool $isHomeDelivery ): void {
		$source = $order->has_shipping_address() ? $orderData['shipping'] : $orderData['billing'];

		$request->setName( $source['first_name'] );
		$request->setSurname( $source['last_name'] );
		if ( $isHomeDelivery ) {
			$request->setStreet( $source['address_1'] );
			$request->setCity( $source['city'] );
			$request->setZip( $source['postcode'] );
		}
		// Shipping address phone is optional.
		$request->setPhone( $orderData['billing']['phone'] );
		if ( ! empty( $source['phone'] ) ) {
			$request->setPhone( $source['phone'] );
		}
	}
}
