<?php
/**
 * Class OrderClient
 *
 * @package Packetery\Api
 */

namespace Packetery\Api;

use Packetery\Api\Soap\Packet;
use Packetery\Carrier\Repository;
use Packetery\Options\Provider;
use WC_Order;

/**
 * Class OrderClient
 *
 * @package Packetery\Api
 */
class OrderClient {
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
	 * @var Packet Packet API.
	 */
	private $packetApi;

	/**
	 * OrderApi constructor.
	 *
	 * @param Provider   $optionsProvider Options Provider.
	 * @param Repository $carrierRepository Carrier repository.
	 * @param Packet     $packetApi Packet API.
	 */
	public function __construct( Provider $optionsProvider, Repository $carrierRepository, Packet $packetApi ) {
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
			$apiPassword = $this->optionsProvider->get_api_password();
			$attributes  = $this->preparePacketAttributes( $order, $orderData );
			// TODO: update before release.
			$logger = wc_get_logger();
			$logger->info( wp_json_encode( $attributes ) );

			$packet = $this->packetApi->createPacket( $apiPassword, $attributes );
			if ( ! empty( $packet->errors ) ) {
				$results['ERROR'][] = $packet->errors;
			} else {
				update_post_meta( $orderData['id'], 'packetery_is_exported', '1' );
				update_post_meta( $orderData['id'], 'packetery_packet_id', $packet->barcode );
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
	 * @return array
	 */
	private function preparePacketAttributes( WC_Order $order, array $orderData ): array {
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
		$attributes  = [
			'number'    => $orderData['id'],
			'name'      => $contactInfo['name'],
			'surname'   => $contactInfo['surname'],
			'email'     => $orderData['billing']['email'],
			'phone'     => $contactInfo['phone'],
			'addressId' => $addressId,
			'value'     => $orderTotalPrice,
			'eshop'     => $this->optionsProvider->get_sender(),
			'weight'    => $weight,
		];
		if ( ! empty( $carrierId ) && empty( $pointId ) ) {
			$attributes['street'] = $contactInfo['street'];
			$attributes['city']   = $contactInfo['city'];
			$attributes['zip']    = $contactInfo['zip'];
		}
		if ( $orderData['payment_method'] === $codMethod ) {
			$attributes['cod'] = $orderTotalPrice;
		}
		if ( ! empty( $pointCarrierId ) ) {
			$attributes['carrierPickupPoint'] = $pointCarrierId;
		}
		if ( true === $checkForRequiredSize ) {
			$carrier = $this->carrierRepository->getById( $carrierId );
			if ( $carrier && $carrier->getRequiresSize() ) {
				$attributes['size'] = [
					'length' => $order->get_meta( 'packetery_length' ),
					'width'  => $order->get_meta( 'packetery_width' ),
					'height' => $order->get_meta( 'packetery_height' ),
				];
			}
		}

		return $attributes;
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
