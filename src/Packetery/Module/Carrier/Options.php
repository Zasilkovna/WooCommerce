<?php
/**
 * Options class.
 *
 * @package Packetery
 */

declare( strict_types=1 );


namespace Packetery\Module\Carrier;

use Packetery\Module\Checkout;

/**
 * Options class.
 */
class Options {

	/**
	 * Options.
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Constructor.
	 *
	 * @param array $options Options.
	 */
	public function __construct( array $options ) {
		$this->options = $options;
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

		return new self( $options );
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
}
