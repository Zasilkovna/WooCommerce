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

	public const ERROR_TRANSLATION_KEY_NUMBER                     = 'validation_error_number';
	public const ERROR_TRANSLATION_KEY_NAME                       = 'validation_error_name';
	public const ERROR_TRANSLATION_KEY_VALUE                      = 'validation_error_value';
	public const ERROR_TRANSLATION_KEY_PICKUP_POINT_OR_CARRIER_ID = 'validation_error_pickup_point_or_carrier_id';
	public const ERROR_TRANSLATION_KEY_ESHOP                      = 'validation_error_eshop';
	public const ERROR_TRANSLATION_KEY_WEIGHT                     = 'validation_error_weight';
	public const ERROR_TRANSLATION_KEY_ADDRESS                    = 'validation_error_address';
	public const ERROR_TRANSLATION_KEY_HEIGHT                     = 'validation_error_height';
	public const ERROR_TRANSLATION_KEY_WIDTH                      = 'validation_error_width';
	public const ERROR_TRANSLATION_KEY_LENGTH                     = 'validation_error_length';
	public const ERROR_TRANSLATION_KEY_CUSTOMS_DECLARATION        = 'validation_error_customs_declaration';

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
	 * Validation errors translations.
	 *
	 * @var array<string, string> $translations
	 */
	private $translations;

	/**
	 * Order constructor.
	 *
	 * @param Address               $addressValidator Address validator.
	 * @param Size                  $sizeValidator    Size validator.
	 * @param array<string, string> $translations     Translations with specified keys.
	 */
	public function __construct( Address $addressValidator, Size $sizeValidator, array $translations ) {
		$this->addressValidator = $addressValidator;
		$this->sizeValidator    = $sizeValidator;
		$this->translations     = $translations;
	}

	/**
	 * Validates data needed to submit packet.
	 *
	 * @param Entity\Order $order Order entity.
	 *
	 * @return bool
	 */
	public function isValid( Entity\Order $order ): bool {
		return empty( $this->validate( $order ) );
	}

	/**
	 * Validates data needed to submit packet and returns array of errors.
	 *
	 * @param Entity\Order $order Order entity.
	 *
	 * @return string[]
	 */
	public function validate( Entity\Order $order ): array {
		$sizeReport = $this->validateSize( $order );
		$errors     = [
			self::ERROR_TRANSLATION_KEY_NUMBER  => ! $order->getNumber(),
			self::ERROR_TRANSLATION_KEY_NAME    => ! $order->getName(),
			self::ERROR_TRANSLATION_KEY_VALUE   => ! $order->getValue(),
			self::ERROR_TRANSLATION_KEY_PICKUP_POINT_OR_CARRIER_ID => ! $order->getPickupPointOrCarrierId(),
			self::ERROR_TRANSLATION_KEY_ESHOP   => ! $order->getEshop(),
			self::ERROR_TRANSLATION_KEY_WEIGHT  => ! $this->validateFinalWeight( $order ),
			self::ERROR_TRANSLATION_KEY_ADDRESS => ! $this->validateAddress( $order ),
			self::ERROR_TRANSLATION_KEY_HEIGHT  => ! $sizeReport->isHeightValid(),
			self::ERROR_TRANSLATION_KEY_WIDTH   => ! $sizeReport->isWidthValid(),
			self::ERROR_TRANSLATION_KEY_LENGTH  => ! $sizeReport->isLengthValid(),
			self::ERROR_TRANSLATION_KEY_CUSTOMS_DECLARATION => $order->hasToFillCustomsDeclaration(),
		];

		$result = [];
		foreach ( $errors as $key => $hasError ) {
			if ( ! $hasError ) {
				continue;
			}

			$result[ $key ] = $this->getTranslation( $key );
		}

		return $result;
	}

	/**
	 * Return the key if translation is not set.
	 *
	 * @param string $key Translation key.
	 * @return string
	 */
	private function getTranslation( string $key ): string {
		if ( empty( $this->translations[ $key ] ) ) {
			return $key;
		}
		return $this->translations[ $key ];
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
	 * @return SizeReport
	 */
	private function validateSize( Entity\Order $order ): SizeReport {
		if ( $order->getCarrier()->requiresSize() ) {
			$size = $order->getSize();
			if ( null === $size ) {
				return new SizeReport( false, false, false );
			}

			return $this->sizeValidator->validate( $size );
		}

		return new SizeReport( true, true, true );
	}

}
