<?php
/**
 * Class ValidatorFactory.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Validator;
use Packetery\Core\Validator\Address;
use Packetery\Core\Validator\Size;

/**
 * Class ValidatorFactory.
 *
 * @package Packetery
 */
class ValidatorFactory {

	/**
	 * Address validator.
	 *
	 * @var Address
	 */
	private $addressValidator;

	/**
	 * Size validator.
	 *
	 * @var Size
	 */
	private $sizeValidator;

	/**
	 * Order constructor.
	 *
	 * @param Address $addressValidator Address validator.
	 * @param Size    $sizeValidator Size validator.
	 */
	public function __construct( Address $addressValidator, Size $sizeValidator ) {
		$this->addressValidator = $addressValidator;
		$this->sizeValidator    = $sizeValidator;
	}

	/**
	 * Gets instance and sets translations.
	 *
	 * @return Validator\Order
	 */
	public function create(): Validator\Order {
		$validator = new Validator\Order( $this->addressValidator, $this->sizeValidator );
		$validator->setTranslations(
			[
				Validator\Order::ERROR_NUMBER  => __( 'Order number is not set.', 'packeta' ),
				Validator\Order::ERROR_NAME    => __( 'Customer name is not set.', 'packeta' ),
				Validator\Order::ERROR_VALUE   => __( 'Order value is not set.', 'packeta' ),
				Validator\Order::ERROR_PICKUP_POINT_OR_CARRIER_ID => __( 'Pickup point or carrier id is not set.', 'packeta' ),
				Validator\Order::ERROR_ESHOP   => __( 'Sender label is not set.', 'packeta' ),
				Validator\Order::ERROR_WEIGHT  => __( 'Weight is not set or is zero.', 'packeta' ),
				Validator\Order::ERROR_ADDRESS => __( 'Address is not set or is incomplete.', 'packeta' ),
				Validator\Order::ERROR_SIZE    => __( 'Order dimensions are not set.', 'packeta' ),
			]
		);

		return $validator;
	}

}
