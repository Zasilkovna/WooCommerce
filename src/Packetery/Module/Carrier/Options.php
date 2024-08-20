<?php
/**
 * Options class.
 *
 * @package Packetery
 */

declare( strict_types=1 );


namespace Packetery\Module\Carrier;

use Packetery\Core\Rounder;

/**
 * Options class.
 */
class Options {

	public const PRICING_TYPE_BY_WEIGHT        = 'byWeight';
	public const PRICING_TYPE_BY_PRODUCT_VALUE = 'byProductValue';

	/**
	 * Option ID.
	 *
	 * @var string
	 */
	private $optionId;

	/**
	 * Options.
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Constructor.
	 *
	 * @param string $optionId Option ID.
	 * @param array  $options  Options.
	 */
	public function __construct( string $optionId, array $options ) {
		$this->optionId = $optionId;
		$this->options  = $options;
	}

	/**
	 * Option ID.
	 *
	 * @return string
	 */
	public function getOptionId(): string {
		return $this->optionId;
	}

	/**
	 * Returns all options as assoc array.
	 *
	 * @return array
	 */
	public function toArray(): array {
		return $this->options;
	}

	/**
	 * Age verification fee.
	 *
	 * @return float|null
	 */
	public function getAgeVerificationFee(): ?float {
		$value = $this->options['age_verification_fee'] ?? null;
		if ( is_numeric( $value ) ) {
			return (float) $value;
		}

		return null;
	}

	/**
	 * Gets type of address validation. One of ['required', 'optional', 'none'].
	 *
	 * @return string
	 */
	public function getAddressValidation(): string {
		$none  = 'none';
		$value = $this->options['address_validation'] ?? $none;
		if ( $value ) {
			return $value;
		}

		return $none;
	}

	/**
	 * Gets custom carrier name.
	 *
	 * @return string|null
	 */
	public function getName(): ?string {
		return ( $this->options['name'] ?? null );
	}

	/**
	 * Tells if carrier is active.
	 *
	 * @return bool
	 */
	public function isActive(): bool {
		return $this->options['active'] ?? false;
	}

	/**
	 * Tells if carrier has coupon free shipping active.
	 *
	 * @return bool
	 */
	public function hasCouponFreeShippingActive(): bool {
		return $this->options['coupon_free_shipping']['active'] ?? false;
	}

	/**
	 * Tells if carrier has coupon free shipping for fees allowed.
	 *
	 * @return bool
	 */
	public function hasCouponFreeShippingForFeesAllowed(): bool {
		return $this->hasCouponFreeShippingActive() && ( $this->options['coupon_free_shipping']['allow_for_fees'] ?? false );
	}

	/**
	 * Tells if carrier has payment method disallowed.
	 *
	 * @param string $method Payment method.
	 *
	 * @return bool
	 */
	public function hasCheckoutPaymentMethodDisallowed( string $method ): bool {
		return in_array( $method, $this->options['disallowed_checkout_payment_methods'] ?? [], true );
	}

	/**
	 * Gets default COD surcharge.
	 *
	 * @return float|null
	 */
	public function getDefaultCODSurcharge(): ?float {
		$value = $this->options['default_COD_surcharge'] ?? null;
		if ( is_numeric( $value ) ) {
			return (float) $value;
		}

		return null;
	}

	/**
	 * Tells if any COD surcharge was configured.
	 *
	 * @return bool
	 */
	public function hasAnyCodSurchargeSetting(): bool {
		if ( null !== $this->getDefaultCODSurcharge() ) {
			return true;
		}

		return ! empty( $this->options['surcharge_limits'] );
	}

	/**
	 * Tells COD rounding type.
	 *
	 * @return int
	 */
	public function getCodRoundingType(): int {
		return $this->options['cod_rounding'] ?? Rounder::DONT_ROUND;
	}

	/**
	 * Gets pricing type.
	 *
	 * @return string
	 */
	public function getPricingType(): string {
		return $this->options[ OptionsPage::FORM_FIELD_PRICING_TYPE ] ?? self::PRICING_TYPE_BY_WEIGHT;
	}

	/**
	 * Checks if carrier has saved options.
	 *
	 * @return bool
	 */
	public function hasOptions(): bool {
		return ! empty( $this->options );
	}

}
