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
use Packetery\Validator;
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
	 * CreatePacket validator.
	 *
	 * @var Validator\CreatePacket
	 */
	private $createPacketValidator;

	/**
	 * Address validator.
	 *
	 * @var Validator\Address
	 */
	private $addressValidator;

	/**
	 * Size validator.
	 *
	 * @var Validator\Size
	 */
	private $sizeValidator;

	/**
	 * OrderApi constructor.
	 *
	 * @param Provider               $optionsProvider Options Provider.
	 * @param Repository             $carrierRepository Carrier repository.
	 * @param Client                 $soapApiClient SOAP API Client.
	 * @param Validator\CreatePacket $createPacketValidator CreatePacket validator.
	 * @param Validator\Address      $addressValidator Address validator.
	 * @param Validator\Size         $sizeValidator Size validator.
	 */
	public function __construct(
		Provider $optionsProvider,
		Repository $carrierRepository,
		Client $soapApiClient,
		Validator\CreatePacket $createPacketValidator,
		Validator\Address $addressValidator,
		Validator\Size $sizeValidator
	) {
		$this->optionsProvider       = $optionsProvider;
		$this->carrierRepository     = $carrierRepository;
		$this->soapApiClient         = $soapApiClient;
		$this->createPacketValidator = $createPacketValidator;
		$this->addressValidator      = $addressValidator;
		$this->sizeValidator         = $sizeValidator;
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
				$createPacketRequest = $this->preparePacketAttributes( $order );
			} catch ( IncompleteRequestException $e ) {
				if ( $logger ) {
					$logger->info( $orderData['id'] . ': ' . $e->getMessage() );
				}
				$resultsCounter['ignored']++;

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
		$orderData   = $order->get_data();
		$orderId     = (string) $orderData['id'];
		$contactInfo = ( $order->has_shipping_address() ? $orderData['shipping'] : $orderData['billing'] );
		// Type cast of $orderTotalPrice is needed, PHPDoc is wrong.
		$orderValue = (float) $order->get_total( 'raw' );
		$entity     = new Entity( $order );

		$this->validateRequiredRequestData( $orderId, $contactInfo, $orderValue, $entity );
		$request = $this->createRequest( $orderId, $contactInfo, $orderValue, $entity );
		$this->addHomeDeliveryDetails( $entity, $contactInfo, $request );

		// Shipping address phone is optional.
		$request->setPhone( $orderData['billing']['phone'] );
		if ( ! empty( $contactInfo['phone'] ) ) {
			$request->setPhone( $contactInfo['phone'] );
		}

		$request->setEmail( $orderData['billing']['email'] );
		$codMethod = $this->optionsProvider->getCodPaymentMethod();
		if ( $orderData['payment_method'] === $codMethod ) {
			$request->setCod( $orderValue );
		}
		$this->addExternalCarrierDetails( $entity, $request );

		return $request;
	}

	/**
	 * Validates if all required data are set.
	 *
	 * @param string|null $id Order id.
	 * @param array|null  $contactInformation Contact info.
	 * @param float|null  $orderTotalPrice Order value.
	 * @param Entity      $entity Order entity.
	 *
	 * @throws IncompleteRequestException For the case request is not eligible to be sent to API.
	 */
	private function validateRequiredRequestData( ?string $id, ?array $contactInformation, ?float $orderTotalPrice, Entity $entity ): void {
		if ( ! $this->createPacketValidator->validate(
			$id,
			$contactInformation['first_name'],
			$contactInformation['last_name'],
			$orderTotalPrice,
			$entity->getWeight(),
			$entity->getAddressId(),
			$this->optionsProvider->get_sender()
		) ) {
			throw new IncompleteRequestException( 'All required packet attributes are not set.' );
		}
	}

	/**
	 * Creates CreatePacket request.
	 *
	 * @param string $orderId Order id.
	 * @param array  $contactInformation Contact info.
	 * @param float  $orderTotalPrice Order value.
	 * @param Entity $entity Order entity.
	 *
	 * @return CreatePacket
	 */
	private function createRequest( string $orderId, array $contactInformation, float $orderTotalPrice, Entity $entity ): CreatePacket {
		return new CreatePacket(
			$orderId,
			$contactInformation['first_name'],
			$contactInformation['last_name'],
			$orderTotalPrice,
			$entity->getWeight(),
			$entity->getAddressId(),
			$this->optionsProvider->get_sender()
		);
	}

	/**
	 * Adds data to request if applicable.
	 *
	 * @param Entity       $entity Order entity.
	 * @param array        $contactInfo Contact info.
	 * @param CreatePacket $request CreatePacket request.
	 *
	 * @throws IncompleteRequestException For the case request is not eligible to be sent to API.
	 */
	private function addHomeDeliveryDetails( Entity $entity, array $contactInfo, CreatePacket $request ): void {
		if ( ! $entity->isHomeDelivery() ) {
			return;
		}
		if ( ! $this->addressValidator->validate( $contactInfo['address_1'], $contactInfo['city'], $contactInfo['postcode'] ) ) {
			throw new IncompleteRequestException( 'Address is not complete.' );
		}
		$address = new Address( $contactInfo['address_1'], $contactInfo['city'], $contactInfo['postcode'] );
		$request->setAddress( $address );
		// Additional address information.
		if ( ! empty( $contactInfo['address_2'] ) ) {
			$request->setNote( $contactInfo['address_2'] );
		}
	}

	/**
	 * Adds data to request if applicable.
	 *
	 * @param Entity       $entity Order entity.
	 * @param CreatePacket $request CreatePacket request.
	 *
	 * @throws IncompleteRequestException For the case request is not eligible to be sent to API.
	 */
	private function addExternalCarrierDetails( Entity $entity, CreatePacket $request ): void {
		if ( ! $entity->isExternalCarrier() ) {
			return;
		}
		if ( $entity->isExternalPickupPointDelivery() ) {
			$pointCarrierId = $entity->getPointCarrierId();
			$request->setCarrierPickupPoint( $pointCarrierId );
		}
		$carrierId = $entity->getCarrierId();
		$carrier   = $this->carrierRepository->getById( (int) $carrierId );
		if ( $carrier && $carrier->requiresSize() ) {
			if ( ! $this->sizeValidator->validate( $entity->getLength(), $entity->getWidth(), $entity->getHeight() ) ) {
				throw new IncompleteRequestException( 'All packet dimensions are not set.' );
			}
			$size = new Size( $entity->getLength(), $entity->getWidth(), $entity->getHeight() );
			$request->setSize( $size );
		}
	}
}
