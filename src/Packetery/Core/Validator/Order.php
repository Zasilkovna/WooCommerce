<?php
/**
 * Class Order
 *
 * @package Packetery\Validator
 */

declare( strict_types=1 );

namespace Packetery\Core\Validator;

use Packetery\Core\Entity;

/**
 * Class Order
 *
 * @package Packetery\Validator
 */
class Order {

	public const ERROR_NUMBER                     = 'number';
	public const ERROR_NAME                       = 'name';
	public const ERROR_VALUE                      = 'value';
	public const ERROR_PICKUP_POINT_OR_CARRIER_ID = 'pickup_point_or_carrier_id';
	public const ERROR_ESHOP                      = 'eshop';
	public const ERROR_WEIGHT                     = 'weight';
	public const ERROR_ADDRESS                    = 'address';
	public const ERROR_SIZE                       = 'size';

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
	 * Validation errors translations, which are expected to be set before usage.
	 *
	 * @var array $translations
	 */
	private $translations = [
		self::ERROR_NUMBER                     => self::ERROR_NUMBER,
		self::ERROR_NAME                       => self::ERROR_NAME,
		self::ERROR_VALUE                      => self::ERROR_VALUE,
		self::ERROR_PICKUP_POINT_OR_CARRIER_ID => self::ERROR_PICKUP_POINT_OR_CARRIER_ID,
		self::ERROR_ESHOP                      => self::ERROR_ESHOP,
		self::ERROR_WEIGHT                     => self::ERROR_WEIGHT,
		self::ERROR_ADDRESS                    => self::ERROR_ADDRESS,
		self::ERROR_SIZE                       => self::ERROR_SIZE,
	];

	/**
	 * Order constructor.
	 *
	 * @param Address $addressValidator Address validator.
	 * @param Size    $sizeValidator    Size validator.
	 */
	public function __construct( Address $addressValidator, Size $sizeValidator ) {
		$this->addressValidator = $addressValidator;
		$this->sizeValidator    = $sizeValidator;
	}

	/**
	 * Validates data needed to submit packet.
	 *
	 * @param Entity\Order $order Order entity.
	 *
	 * @return bool
	 */
	public function validate( Entity\Order $order ): bool {
		return (
			$order->getNumber() &&
			$order->getName() &&
			$order->getValue() &&
			$order->getPickupPointOrCarrierId() &&
			$order->getEshop() &&
			$this->validateFinalWeight( $order ) &&
			$this->validateAddress( $order ) &&
			$this->validateSize( $order )
		);
	}

	/**
	 * Validates data needed to submit packet and returns array of errors.
	 *
	 * @param Entity\Order $order Order entity.
	 *
	 * @return string[]
	 */
	public function getValidationErrors( Entity\Order $order ): array {
		$errors = [];
		if ( ! $order->getNumber() ) {
			$errors[] = $this->translations[ self::ERROR_NUMBER ];
		}
		if ( ! $order->getName() ) {
			$errors[] = $this->translations[ self::ERROR_NAME ];
		}
		if ( ! $order->getValue() ) {
			$errors[] = $this->translations[ self::ERROR_VALUE ];
		}
		if ( ! $order->getPickupPointOrCarrierId() ) {
			$errors[] = $this->translations[ self::ERROR_PICKUP_POINT_OR_CARRIER_ID ];
		}
		if ( ! $order->getEshop() ) {
			$errors[] = $this->translations[ self::ERROR_ESHOP ];
		}
		if ( ! $this->validateFinalWeight( $order ) ) {
			$errors[] = $this->translations[ self::ERROR_WEIGHT ];
		}
		if ( ! $this->validateAddress( $order ) ) {
			$errors[] = $this->translations[ self::ERROR_ADDRESS ];
		}
		if ( ! $this->validateSize( $order ) ) {
			$errors[] = $this->translations[ self::ERROR_SIZE ];
		}

		return $errors;
	}

	/**
	 * Validates delivery address if needed.
	 *
	 * @param Entity\Order $order Order entity.
	 *
	 * @return bool
	 */
	private function validateAddress( Entity\Order $order ): bool {
		if ( $order->isHomeDelivery() ) {
			$address = $order->getDeliveryAddress();
			if ( null === $address ) {
				return false;
			}

			return $this->addressValidator->validate( $address );
		}

		return true;
	}

	/**
	 * Validate weight.
	 *
	 * @param Entity\Order $order Order.
	 *
	 * @return bool
	 */
	private function validateFinalWeight( Entity\Order $order ): bool {
		return null !== $order->getFinalWeight() && $order->getFinalWeight() > 0;
	}

	/**
	 * Validates size if needed.
	 *
	 * @param Entity\Order $order Order entity.
	 *
	 * @return bool
	 */
	public function validateSize( Entity\Order $order ): bool {
		if ( $order->getCarrier()->requiresSize() ) {
			$size = $order->getSize();
			if ( null === $size ) {
				return false;
			}

			return $this->sizeValidator->validate( $size );
		}

		return true;
	}

	/**
	 * Sets translations with specified keys.
	 *
	 * @param string[] $translations Translations to set.
	 */
	public function setTranslations( array $translations ): void {
		$this->translations = $translations;
	}

}
