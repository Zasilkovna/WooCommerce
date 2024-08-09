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
			Validator\Order::ERROR_TRANSLATION_KEY_NUMBER  => __( 'Order number is not set.', 'packeta' ),
			Validator\Order::ERROR_TRANSLATION_KEY_NAME    => __( 'Customer name is not set.', 'packeta' ),
			Validator\Order::ERROR_TRANSLATION_KEY_VALUE   => __( 'Order value is not set.', 'packeta' ),
			Validator\Order::ERROR_TRANSLATION_KEY_PICKUP_POINT_OR_CARRIER_ID => __( 'Pickup point or carrier id is not set.', 'packeta' ),
			Validator\Order::ERROR_TRANSLATION_KEY_ESHOP   => __( 'Sender label is not set.', 'packeta' ),
			Validator\Order::ERROR_TRANSLATION_KEY_WEIGHT  => __( 'Weight is not set or is zero.', 'packeta' ),
			Validator\Order::ERROR_TRANSLATION_KEY_ADDRESS => __( 'Address is not set or is incomplete.', 'packeta' ),
			Validator\Order::ERROR_TRANSLATION_KEY_HEIGHT  => __( 'Order height is not set.', 'packeta' ),
			Validator\Order::ERROR_TRANSLATION_KEY_LENGTH  => __( 'Order length is not set.', 'packeta' ),
			Validator\Order::ERROR_TRANSLATION_KEY_WIDTH   => __( 'Order width is not set.', 'packeta' ),
			Validator\Order::ERROR_TRANSLATION_KEY_CUSTOMS_DECLARATION => __( 'Customs declaration is not set.', 'packeta' ),
		];
	}

}
