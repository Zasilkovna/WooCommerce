<?php
/**
 * Options class.
 *
 * @package Packetery
 */

declare( strict_types=1 );


namespace Packetery\Module\Carrier;

use Packetery\Core\Rounder;
use Packetery\Module\Checkout;

/**
 * Options class.
 */
class Options {

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
	 * Creates instance by option ID.
	 *
	 * @param string $optionId Option ID.
	 *
	 * @return static
	 */
	public static function createByOptionId( string $optionId ): self {
		$options = get_option( $optionId );
		if ( empty( $options ) ) {
			$options = [];
		}

		return new self( $optionId, $options );
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
	 * Creates instance by carrier ID.
	 *
	 * @param string $carrierId Carrier ID.
	 *
	 * @return static
	 */
	public static function createByCarrierId( string $carrierId ): self {
		$optionId = Checkout::CARRIER_PREFIX . $carrierId;
		return self::createByOptionId( $optionId );
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
	 * Gets maximum COD value.
	 *
	 * @return float|null
	 */
	public function getMaximumCodValue(): ?float {
		$value = $this->options['maximum_cod_value'] ?? null;
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
}
