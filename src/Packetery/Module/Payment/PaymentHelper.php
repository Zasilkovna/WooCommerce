<?php
/**
 * Class PaymentHelper.
 *
 * @package Packetery
 */

declare( strict_types=1 );


namespace Packetery\Module\Payment;

use Packetery\Module\Options\OptionsProvider;

/**
 * Class PaymentHelper.
 *
 * @package Packetery
 */
class PaymentHelper {

	/**
	 * Options provider.
	 *
	 * @var OptionsProvider
	 */
	private $optionsProvider;

	/**
	 * Constructor.
	 *
	 * @param OptionsProvider $optionsProvider Options provider.
	 */
	public function __construct( OptionsProvider $optionsProvider ) {
		$this->optionsProvider = $optionsProvider;
	}

	/**
	 * Tells if given payment method is COD payment method.
	 *
	 * @param string $paymentMethod Payment method.
	 *
	 * @return bool
	 */
	public function isCodPaymentMethod( string $paymentMethod ): bool {
		if ( empty( $paymentMethod ) ) {
			return false;
		}

		return in_array( $paymentMethod, $this->optionsProvider->getCodPaymentMethods(), true );
	}
}
