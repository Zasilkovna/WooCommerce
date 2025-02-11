<?php

declare( strict_types=1 );

namespace Packetery\Module\Checkout;

use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Order;
use Packetery\Nette\Http\Request;

use function is_array;

class CheckoutStorage {

	public const TRANSIENT_CHECKOUT_DATA_PREFIX = 'packeta_checkout_data_';

	/**
	 * @var Request
	 */
	private $httpRequest;

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * @var WcAdapter
	 */
	private $wcAdapter;

	public function __construct(
		Request $httpRequest,
		WpAdapter $wpAdapter,
		WcAdapter $wcAdapter
	) {
		$this->httpRequest = $httpRequest;
		$this->wpAdapter   = $wpAdapter;
		$this->wcAdapter   = $wcAdapter;
	}

	/**
	 * @return mixed
	 */
	public function getFromTransient() {
		return $this->wpAdapter->getTransient( $this->getTransientNamePacketaCheckoutData() );
	}

	/**
	 * @param array<string, array<string, mixed>> $savedData
	 */
	public function setTransient( array $savedData ): void {
		$this->wpAdapter->setTransient(
			$this->getTransientNamePacketaCheckoutData(),
			$savedData,
			DAY_IN_SECONDS
		);
	}

	public function deleteTransient(): void {
		$this->wpAdapter->deleteTransient( $this->getTransientNamePacketaCheckoutData() );
	}

	/**
	 * Gets checkout POST data including stored pickup point if not present in the data.
	 *
	 * @param string   $chosenShippingMethod Chosen shipping method id.
	 * @param int|null $orderId              Id of order to be updated.
	 *
	 * @return array
	 */
	public function getPostDataIncludingStoredData( string $chosenShippingMethod, ?int $orderId = null ): array {
		$checkoutData = $this->httpRequest->getPost();
		if ( $checkoutData !== null && ! is_array( $checkoutData ) ) {
			$checkoutData = null;
		}
		$savedCheckoutData = $this->getFromTransient();

		if (
			! isset( $savedCheckoutData[ $chosenShippingMethod ] ) &&
			(
				$checkoutData === null ||
				( is_array( $savedCheckoutData ) && count( $checkoutData ) === 0 )
			)
		) {
			$this->logInvalidOrderData( $chosenShippingMethod, $checkoutData, $savedCheckoutData, $orderId );

			return [];
		}

		if (
			! is_array( $savedCheckoutData ) ||
			! isset( $savedCheckoutData[ $chosenShippingMethod ] ) ||
			! is_array( $savedCheckoutData[ $chosenShippingMethod ] )
		) {
			return $checkoutData;
		}

		$savedCarrierData = $savedCheckoutData[ $chosenShippingMethod ];
		if ( $this->isKeyPresentInSavedDataButNotInPostData( $checkoutData, $savedCarrierData, Order\Attribute::POINT_ID ) ) {
			foreach ( Order\Attribute::$pickupPointAttributes as $attribute ) {
				$checkoutData[ $attribute['name'] ] = $savedCarrierData[ $attribute['name'] ];
			}
		}

		if ( $this->isKeyPresentInSavedDataButNotInPostData( $checkoutData, $savedCarrierData, Order\Attribute::ADDRESS_IS_VALIDATED ) ) {
			foreach ( Order\Attribute::$homeDeliveryAttributes as $attribute ) {
				$checkoutData[ $attribute['name'] ] = $savedCarrierData[ $attribute['name'] ];
			}
		}

		if ( $this->isKeyPresentInSavedDataButNotInPostData( $checkoutData, $savedCarrierData, Order\Attribute::CAR_DELIVERY_ID ) ) {
			foreach ( Order\Attribute::$carDeliveryAttributes as $attribute ) {
				$checkoutData[ $attribute['name'] ] = $savedCarrierData[ $attribute['name'] ];
			}
		}

		if ( $this->isKeyPresentInSavedDataButNotInPostData( $checkoutData, $savedCarrierData, Order\Attribute::CARRIER_ID ) ) {
			$checkoutData[ Order\Attribute::CARRIER_ID ] = $savedCarrierData[ Order\Attribute::CARRIER_ID ];
		}

		return $checkoutData;
	}

	/**
	 * Gets name of transient for selected pickup point.
	 */
	private function getTransientNamePacketaCheckoutData(): string {
		if ( $this->wpAdapter->isUserLoggedIn() ) {
			$token = $this->wpAdapter->getSessionToken();
		} else {
			$this->wcAdapter->initializeSession();
			$token = $this->wcAdapter->sessionGetCustomerId();
		}

		return self::TRANSIENT_CHECKOUT_DATA_PREFIX . $token;
	}

	/**
	 * @param string     $chosenShippingMethod
	 * @param array|null $checkoutData
	 * @param mixed      $savedCheckoutData Data from transient.
	 * @param int|null   $orderId
	 */
	private function logInvalidOrderData( string $chosenShippingMethod, ?array $checkoutData, $savedCheckoutData, ?int $orderId ): void {
		$wcLogger  = $this->wcAdapter->getLogger();
		$dataToLog = [
			'chosenShippingMethod' => $chosenShippingMethod,
			'checkoutData'         => $checkoutData,
			'savedCheckoutData'    => $savedCheckoutData,
		];
		if ( $orderId !== null ) {
			$dataToLog['orderId'] = $orderId;
		}
		$wcLogger->warning(
			sprintf(
				'Data of the order to be validated or saved are not set: %s',
				$this->wpAdapter->jsonEncode( $dataToLog )
			),
			[ 'source' => 'packeta' ]
		);
	}

	private function isKeyPresentInSavedDataButNotInPostData( array $checkoutData, array $savedCarrierData, string $key ): bool {
		$isKeyMissingInCheckoutData     = ! isset( $checkoutData[ $key ] ) || $checkoutData[ $key ] === '';
		$isKeyPresentInSavedCarrierData = isset( $savedCarrierData[ $key ] ) && $savedCarrierData[ $key ] !== '';

		return $isKeyMissingInCheckoutData && $isKeyPresentInSavedCarrierData;
	}
}
