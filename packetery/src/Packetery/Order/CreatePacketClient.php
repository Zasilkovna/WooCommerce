<?php
/**
 * Class OrderClient
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
 * Class OrderClient
 *
 * @package Packetery\Api
 */
class CreatePacketClient {
	// todo vyhledove prejmenovat
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
	 * Packet API.
	 *
	 * @var Client Packet API.
	 */
	private $packetApi;

	/**
	 * OrderApi constructor.
	 *
	 * @param Provider   $optionsProvider Options Provider.
	 * @param Repository $carrierRepository Carrier repository.
	 * @param Client     $packetApi Packet API.
	 */
	public function __construct( Provider $optionsProvider, Repository $carrierRepository, Client $packetApi ) {
		$this->optionsProvider   = $optionsProvider;
		$this->carrierRepository = $carrierRepository;
		$this->packetApi         = $packetApi;
	}

	/**
	 * Submits packet data to Packeta API.
	 *
	 * @param WC_Order $order WC order.
	 * @param array    $results Results.
	 *
	 * @return array
	 */
	public function submitPacket( WC_Order $order, array $results ): array {
		$orderData          = $order->get_data();
		$shippingMethods    = $order->get_shipping_methods();
		$shippingMethod     = $shippingMethods[ array_keys( $shippingMethods )[0] ];
		$shippingMethodData = $shippingMethod->get_data();
		$shippingMethodId   = $shippingMethodData['method_id'];
		if ( 'packetery_shipping_method' === $shippingMethodId && ! $order->get_meta( 'packetery_is_exported' ) ) {
			$apiPassword         = $this->optionsProvider->get_api_password();
			$createPacketRequest = $this->preparePacketAttributes( $order, $orderData );
			// TODO: update before release.
			$logger = wc_get_logger();
			$logger->info( wp_json_encode( $createPacketRequest ) );

			$packet = $this->packetApi->createPacket( $apiPassword, $createPacketRequest );
			if ( $packet->getErrors() ) {
				$results['ERROR'][] = $packet->getErrors();
			} else {
				update_post_meta( $orderData['id'], 'packetery_is_exported', '1' );
				update_post_meta( $orderData['id'], 'packetery_packet_id', $packet->getBarcode() );
				$results['SUCCESS'][] = $orderData['id'];
			}
		} else {
			$results['INFO'][] = $orderData['id'];
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
		$weight          = (float) $order->get_meta( 'packetery_weight' );
		$orderTotalPrice = $order->get_total( 'raw' );
		$codMethod       = $this->optionsProvider->getCodPaymentMethod();

		$checkForRequiredSize = false;
		$pointId              = $order->get_meta( 'packetery_point_id' );
		$carrierId            = $order->get_meta( 'packetery_carrier_id' );
		$pointCarrierId       = $order->get_meta( 'packetery_point_carrier_id' );
		if ( ! empty( $pointCarrierId ) || ( ! empty( $carrierId ) && empty( $pointId ) ) ) {
			// External pickup points or home delivery.
			$addressId            = $carrierId;
			$checkForRequiredSize = true;
		} else {
			// Internal pickup points.
			$addressId = $pointId;
		}

		$contactInfo = $this->getContactInfo( $order, $orderData );

		$request = new CreatePacket();
		$request->setNumber( $orderData['id'] );
		$request->setName( $contactInfo['name'] );
		$request->setSurname( $contactInfo['surname'] );
		$request->setEmail( $orderData['billing']['email'] );
		$request->setPhone( $contactInfo['phone'] );
		$request->setAddressId( $addressId );
		$request->setValue( $orderTotalPrice );
		$request->setEshop( $this->optionsProvider->get_sender() );
		$request->setWeight( $weight );

		if ( ! empty( $carrierId ) && empty( $pointId ) ) {
			$request->setStreet( $contactInfo['street'] );
			$request->setCity( $contactInfo['city'] );
			$request->setZip( $contactInfo['zip'] );
		}
		if ( $orderData['payment_method'] === $codMethod ) {
			$request->setCod( $orderTotalPrice );
		}
		if ( ! empty( $pointCarrierId ) ) {
			$request->setCarrierPickupPoint( $pointCarrierId );
		}
		if ( true === $checkForRequiredSize ) {
			$carrier = $this->carrierRepository->getById( $carrierId );
			if ( $carrier && $carrier->requiresSize() ) {
				$request->setSize(
					[
						'length' => $order->get_meta( 'packetery_length' ),
						'width'  => $order->get_meta( 'packetery_width' ),
						'height' => $order->get_meta( 'packetery_height' ),
					]
				);
			}
		}

		return $request;
	}

	/**
	 * Prepares contact information.
	 *
	 * @param WC_Order $order WC order.
	 * @param array    $orderData Order data.
	 *
	 * @return array
	 */
	private function getContactInfo( WC_Order $order, array $orderData ):array {
		$contactInfo = [ 'phone' => $orderData['billing']['phone'] ];
		if ( $order->has_shipping_address() ) {
			$contactInfo['name']    = $orderData['shipping']['first_name'];
			$contactInfo['surname'] = $orderData['shipping']['last_name'];
			$contactInfo['street']  = $orderData['shipping']['address_1'];
			$contactInfo['city']    = $orderData['shipping']['city'];
			$contactInfo['zip']     = $orderData['shipping']['postcode'];
			if ( ! empty( $orderData['shipping']['phone'] ) ) {
				$contactInfo['phone'] = $orderData['shipping']['phone'];
			}
		} else {
			$contactInfo['name']    = $orderData['billing']['first_name'];
			$contactInfo['surname'] = $orderData['billing']['last_name'];
			$contactInfo['street']  = $orderData['billing']['address_1'];
			$contactInfo['city']    = $orderData['billing']['city'];
			$contactInfo['zip']     = $orderData['billing']['postcode'];
		}

		return $contactInfo;
	}
}
