<?php
/**
 * Class ValidatorTranslations.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Validator;

/**
 * Class ValidatorTranslations.
 *
 * @package Packetery
 */
class ValidatorTranslations {

	/**
	 * Translations with specified keys.
	 *
	 * @return array
	 */
	public function get(): array {
		return [
			Validator\Order::TRANSLATION_KEY_NUMBER_ERROR  => __( 'Order number is not set.', 'packeta' ),
			Validator\Order::TRANSLATION_KEY_NAME_ERROR    => __( 'Customer name is not set.', 'packeta' ),
			Validator\Order::TRANSLATION_KEY_VALUE_ERROR   => __( 'Order value is not set.', 'packeta' ),
			Validator\Order::TRANSLATION_KEY_PICKUP_POINT_OR_CARRIER_ID_ERROR => __( 'Pickup point or carrier id is not set.', 'packeta' ),
			Validator\Order::TRANSLATION_KEY_ESHOP_ERROR   => __( 'Sender label is not set.', 'packeta' ),
			Validator\Order::TRANSLATION_KEY_WEIGHT_ERROR  => __( 'Weight is not set or is zero.', 'packeta' ),
			Validator\Order::TRANSLATION_KEY_ADDRESS_ERROR => __( 'Address is not set or is incomplete.', 'packeta' ),
			Validator\Order::TRANSLATION_KEY_SIZE_ERROR    => __( 'Order dimensions are not set.', 'packeta' ),
		];
	}

}
