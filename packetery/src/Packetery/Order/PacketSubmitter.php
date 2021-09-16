<?php
/**
 * Class PacketSubmitter
 *
 * @package Packetery\Api
 */

namespace Packetery\Order;

use Packetery\Api\Soap\Client;
use Packetery\Api\Soap\Request\CreatePacket;
use Packetery\Carrier\Repository;
use Packetery\Options\Provider;
use WC_Order;

/**
 * Class PacketSubmitter
 *
 * TODO: better name.
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
	public function submitPacket( WC_Order $order, array $results ): array {
		$orderData       = $order->get_data();
		$shippingMethods = $order->get_shipping_methods();
		$shippingMethod  = reset( $shippingMethods );

		$shippingMethodData = $shippingMethod->get_data();
		$shippingMethodId   = $shippingMethodData['method_id'];
		if ( 'packetery_shipping_method' === $shippingMethodId && ! $order->get_meta( Entity::META_IS_EXPORTED ) ) {
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
		$orderTotalPrice = $order->get_total( 'raw' );
		$codMethod       = $this->optionsProvider->getCodPaymentMethod();

		$checkForRequiredSize  = false;
		$pointId               = $order->get_meta( Entity::META_POINT_ID );
		$carrierId             = $order->get_meta( Entity::META_CARRIER_ID );
		$pointCarrierId        = $order->get_meta( Entity::META_POINT_CARRIER_ID );
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
		$request->setAddressId( $addressId );
		$request->setValue( $orderTotalPrice );
		$request->setEshop( $this->optionsProvider->get_sender() );
		$request->setWeight( (float) $order->get_meta( 'packetery_weight' ) );
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
					(float) $order->get_meta( 'packetery_length' ),
					(float) $order->get_meta( 'packetery_width' ),
					(float) $order->get_meta( 'packetery_height' )
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
		$request->setPhone( $orderData['billing']['phone'] );
		if ( $order->has_shipping_address() ) {
			$request->setName( $orderData['shipping']['first_name'] );
			$request->setSurname( $orderData['shipping']['last_name'] );
			if ( $isHomeDelivery ) {
				$request->setStreet( $orderData['shipping']['address_1'] );
				$request->setCity( $orderData['shipping']['city'] );
				$request->setZip( $orderData['shipping']['postcode'] );
			}
			if ( ! empty( $orderData['shipping']['phone'] ) ) {
				$request->setPhone( $orderData['shipping']['phone'] );
			}
		} else {
			$request->setName( $orderData['billing']['first_name'] );
			$request->setSurname( $orderData['billing']['last_name'] );
			if ( $isHomeDelivery ) {
				$request->setStreet( $orderData['billing']['address_1'] );
				$request->setCity( $orderData['billing']['city'] );
				$request->setZip( $orderData['billing']['postcode'] );
			}
		}
	}
}
