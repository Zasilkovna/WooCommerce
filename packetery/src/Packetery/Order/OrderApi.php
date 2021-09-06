<?php
/**
 * Class OrderApi.
 *
 * @package Packetery\Order
 */

namespace Packetery\Order;

use Packetery\Carrier\Repository;
use Packetery\Options\Provider;
use SoapClient;
use SoapFault;
use WC_Order;

/**
 * Class OrderApi.
 *
 * @package Packetery\Order
 */
class OrderApi {
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
	 * OrderApi constructor.
	 *
	 * @param Provider   $optionsProvider Options Provider.
	 * @param Repository $carrierRepository Carrier repository.
	 */
	public function __construct( Provider $optionsProvider, Repository $carrierRepository ) {
		$this->optionsProvider   = $optionsProvider;
		$this->carrierRepository = $carrierRepository;
	}

	/**
	 * Submits packet data to Packeta API.
	 *
	 * @param WC_Order $order WC order.
	 * @param array    $results Results.
	 *
	 * @return array
	 */
	public function createPacket( WC_Order $order, array $results ): array {
		$orderData   = $order->get_data();
		$soapClient  = new SoapClient( 'http://www.zasilkovna.cz/api/soap.wsdl' );
		$apiPassword = $this->optionsProvider->get_api_password();

		$shippingMethods    = $order->get_shipping_methods();
		$shippingMethod     = $shippingMethods[ array_keys( $shippingMethods )[0] ];
		$shippingMethodData = $shippingMethod->get_data();
		$shippingMethodId   = $shippingMethodData['method_id'];

		if ( 'packetery_shipping_method' === $shippingMethodId && ! $order->get_meta( 'packetery_is_exported' ) ) {
			try {
				$attributes = $this->preparePacketAttributes( $order, $orderData );

				// TODO: update before release.
				$logger = wc_get_logger();
				$logger->info( wp_json_encode( $attributes ) );

				$packet = $soapClient->createPacket( $apiPassword, $attributes );
				update_post_meta( $orderData['id'], 'packetery_is_exported', '1' );
				update_post_meta( $orderData['id'], 'packetery_packet_id', $packet->barcode );
				$results['SUCCESS'][] = $orderData['id'];
			} catch ( SoapFault $exception ) {
				$errors             = $this->getSoapFaultErrors( $exception );
				$results['ERROR'][] = $errors;
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
		$savedWeight = $order->get_meta( 'packetery_weight' );
		if ( $savedWeight ) {
			$weight = $savedWeight;
		} else {
			$weight = 0;
			foreach ( $order->get_items() as $item ) {
				$quantity      = $item->get_quantity();
				$product       = $item->get_product();
				$productWeight = $product->get_weight();
				$weight       += ( $productWeight * $quantity );
			}
		}

		// TODO: replace with $this->options_provider->get_cod_payment_method();.
		$codMethod = 'cod';
		$cod       = 0;
		if ( $orderData['payment_method'] === $codMethod ) {
			$cod = $order->get_total( 'raw' );
		}

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

		$attributes = [
			'number'    => $orderData['id'],
			'name'      => ( $orderData['shipping']['first_name'] ? $orderData['shipping']['first_name'] : $orderData['billing']['first_name'] ),
			'surname'   => ( $orderData['shipping']['last_name'] ? $orderData['shipping']['last_name'] : $orderData['billing']['last_name'] ),
			'email'     => $orderData['billing']['email'],
			'phone'     => $orderData['billing']['phone'],
			'addressId' => $addressId,
			'cod'       => $cod,
			'value'     => $order->get_total( 'raw' ),
			'eshop'     => $this->optionsProvider->get_sender(),
			'weight'    => $weight,
		];
		if ( ! empty( $carrierId ) && empty( $pointId ) ) {
			$attributes['street'] = ( $orderData['shipping']['address_1'] ? $orderData['shipping']['address_1'] : $orderData['billing']['address_1'] );
			$attributes['city']   = ( $orderData['shipping']['city'] ? $orderData['shipping']['city'] : $orderData['billing']['city'] );
			$attributes['zip']    = ( $orderData['shipping']['postcode'] ? $orderData['shipping']['postcode'] : $orderData['billing']['postcode'] );
		}
		if ( ! empty( $pointCarrierId ) ) {
			$attributes['carrierPickupPoint'] = $pointCarrierId;
		}
		if ( true === $checkForRequiredSize ) {
			$requiresSize = $this->carrierRepository->requiresSize( $carrierId );
			if ( $requiresSize ) {
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
	 * Gets human readable errors form SoapFault exception.
	 *
	 * @param SoapFault $exception Exception.
	 *
	 * @return string
	 */
	private function getSoapFaultErrors( SoapFault $exception ): string {
		$errors = '';

		if ( isset( $exception->detail->PacketAttributesFault->attributes->fault ) ) {
			if ( is_array( $exception->detail->PacketAttributesFault->attributes->fault ) && count( $exception->detail->PacketAttributesFault->attributes->fault ) > 1 ) {
				foreach ( $exception->detail->PacketAttributesFault->attributes->fault as $fault ) {
					$errors .= $fault->name . ': ' . $fault->fault . ' ';
				}
			} else {
				$fault   = $exception->detail->PacketAttributesFault->attributes->fault;
				$errors .= $fault->name . ': ' . $fault->fault . ' ';
			}
		}

		if ( '' === $errors ) {
			$errors = $exception->faultstring;
		}

		// TODO: update before release.
		$logger = wc_get_logger();
		$logger->error( $errors );

		return $errors;
	}
}
