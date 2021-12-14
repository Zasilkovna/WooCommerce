<?php
/**
 * Options class.
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
	 * Carrier ID.
	 *
	 * @param string $carrierId Carrier ID.
	 *
	 * @return static
	 */
	public static function createByCarrierId( string $carrierId ): self {
		$optionId = Checkout::CARRIER_PREFIX . $carrierId;
		$options  = get_option( $optionId );
		if ( empty( $options ) ) {
			$options = [];
		}

		return new self( $options );
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
