<?php

declare( strict_types=1 );

namespace Packetery\Module\Checkout;

use Packetery\Module\Carrier;
use Packetery\Module\Carrier\CarDeliveryConfig;
use Packetery\Module\Carrier\PacketaPickupPointsConfig;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\ShippingMethod;
use Packetery\Nette\Http\Request;
use WC_Shipping_Rate;

class CheckoutService {

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * @var WcAdapter
	 */
	private $wcAdapter;

	/**
	 * @var Request
	 */
	private $httpRequest;

	/**
	 * @var CarDeliveryConfig
	 */
	private $carDeliveryConfig;

	/**
	 * @var Carrier\Repository
	 */
	private $carrierRepository;

	/**
	 * @var Carrier\EntityRepository
	 */
	private $carrierEntityRepository;

	/**
	 * @var PacketaPickupPointsConfig
	 */
	private $pickupPointsConfig;

	/**
	 * @var OptionsProvider
	 */
	private $optionsProvider;

	public function __construct(
		WpAdapter $wpAdapter,
		WcAdapter $wcAdapter,
		Request $httpRequest,
		CarDeliveryConfig $carDeliveryConfig,
		Carrier\Repository $carrierRepository,
		Carrier\EntityRepository $carrierEntityRepository,
		PacketaPickupPointsConfig $pickupPointsConfig,
		OptionsProvider $optionsProvider
	) {
		$this->wpAdapter               = $wpAdapter;
		$this->wcAdapter               = $wcAdapter;
		$this->httpRequest             = $httpRequest;
		$this->carDeliveryConfig       = $carDeliveryConfig;
		$this->carrierRepository       = $carrierRepository;
		$this->carrierEntityRepository = $carrierEntityRepository;
		$this->pickupPointsConfig      = $pickupPointsConfig;
		$this->optionsProvider         = $optionsProvider;
	}

	/**
	 * Calculates shipping without using POST data and returns id of chosen shipping rate.
	 *
	 * @return string
	 */
	public function calculateShippingAndGetId(): string {
		$chosenShippingRates = $this->wcAdapter->cartCalculateShipping();
		$chosenShippingRate  = array_shift( $chosenShippingRates );

		if ( $chosenShippingRate instanceof WC_Shipping_Rate ) {
			return $this->removeShippingMethodPrefix( $chosenShippingRate->get_id() );
		}

		return '';
	}

	/**
	 * Get chosen shipping rate id.
	 *
	 * @return string
	 */
	public function getChosenMethod(): string {
		$postedShippingMethodArray = $this->httpRequest->getPost( 'shipping_method' );

		if ( null !== $postedShippingMethodArray ) {
			return $this->removeShippingMethodPrefix( current( $postedShippingMethodArray ) );
		}

		return $this->calculateShippingAndGetId();
	}

	/**
	 * Gets ShippingRate's ID of extended id.
	 *
	 * @param string $chosenMethod Chosen shipping method.
	 *
	 * @return string
	 */
	public function removeShippingMethodPrefix( string $chosenMethod ): string {
		return str_replace( ShippingMethod::PACKETERY_METHOD_ID . ':', '', $chosenMethod );
	}

	/**
	 * Checks if chosen shipping method is one of packetery.
	 *
	 * @param string $chosenMethod Chosen shipping method.
	 *
	 * @return bool
	 */
	public function isPacketeryShippingMethod( string $chosenMethod ): bool {
		$optionId = $this->removeShippingMethodPrefix( $chosenMethod );

		return Carrier\OptionPrefixer::isOptionId( $optionId );
	}

	/**
	 * Gets feed ID or artificially created ID for internal purposes.
	 *
	 * @param string $chosenMethod Chosen shipping method.
	 *
	 * @return string|null
	 */
	public function getCarrierIdFromShippingMethod( string $chosenMethod ): ?string {
		if ( ! $this->isPacketeryShippingMethod( $chosenMethod ) ) {
			return null;
		}

		$optionId = $this->removeShippingMethodPrefix( $chosenMethod );

		return Carrier\OptionPrefixer::removePrefix( $optionId );
	}

	/**
	 * Check if chosen shipping rate is bound with Packeta pickup points
	 *
	 * @return bool
	 */
	public function isPickupPointOrder(): bool {
		$carrierId = $this->getCarrierIdFromShippingMethod( $this->getChosenMethod() );

		return null !== $carrierId && $this->isPickupPointCarrier( $carrierId );
	}

	/**
	 * Checks if chosen carrier has pickup points and sets carrier id in provided array.
	 *
	 * @param string $carrierId Carrier id.
	 *
	 * @return bool
	 */
	private function isPickupPointCarrier( string $carrierId ): bool {
		if ( $this->pickupPointsConfig->isInternalPickupPointCarrier( $carrierId ) ) {
			return true;
		}

		return $this->carrierRepository->hasPickupPoints( (int) $carrierId );
	}

	/**
	 * Check if chosen shipping rate is bound with Packeta home delivery
	 *
	 * @return bool
	 */
	public function isHomeDeliveryOrder(): bool {
		$chosenMethod = $this->getChosenMethod();
		$carrierId    = $this->getCarrierIdFromShippingMethod( $chosenMethod );

		return null !== $carrierId && $this->carrierEntityRepository->isHomeDeliveryCarrier( $carrierId );
	}

	/**
	 * Check if chosen shipping rate is bound with Packeta car delivery
	 *
	 * @return bool
	 */
	public function isCarDeliveryOrder(): bool {
		$chosenMethod = $this->getChosenMethod();
		$carrierId    = $this->getCarrierIdFromShippingMethod( $chosenMethod );

		return null !== $carrierId && $this->carDeliveryConfig->isCarDeliveryCarrier( $carrierId );
	}

	/**
	 * Gets customer country from WC cart.
	 *
	 * @return string
	 */
	public function getCustomerCountryOrEmpty(): string {
		$country = '';
		if ( null !== $this->wcAdapter->customerGetShippingCountry() ) {
			$country = strtolower( $this->wcAdapter->customerGetShippingCountry() );
		} elseif ( null !== $this->wcAdapter->customerGetBillingCountry() ) {
			$country = strtolower( $this->wcAdapter->customerGetBillingCountry() );
		}

		return $country;
	}

	/**
	 * Checks if Blocks are used in checkout.
	 *
	 * @return bool
	 */
	public function areBlocksUsedInCheckout(): bool {
		$checkoutDetection = $this->optionsProvider->getCheckoutDetection();

		if ( OptionsProvider::BLOCK_CHECKOUT_DETECTION === $checkoutDetection ) {
			return true;
		}

		if ( OptionsProvider::CLASSIC_CHECKOUT_DETECTION === $checkoutDetection ) {
			return false;
		}

		if (
			$this->wpAdapter->hasBlock(
				'woocommerce/checkout',
				$this->wpAdapter->getPostField(
					'post_content',
					$this->wcAdapter->getPageId( 'checkout' )
				)
			) ) {
			return true;
		}

		return false;
	}
}
