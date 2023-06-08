<?php
/**
 * Class Order
 *
 * @package Packetery\Validator
 */

declare( strict_types=1 );

namespace Packetery\Core\Validator;

use Packetery\Core\Entity;
use Packetery\Core\TranslationMissingException;

/**
 * Class Order
 *
 * @package Packetery\Validator
 */
class Order {

	public const TRANSLATION_KEY_NUMBER_ERROR                     = 'number';
	public const TRANSLATION_KEY_NAME_ERROR                       = 'name';
	public const TRANSLATION_KEY_VALUE_ERROR                      = 'value';
	public const TRANSLATION_KEY_PICKUP_POINT_OR_CARRIER_ID_ERROR = 'pickup_point_or_carrier_id';
	public const TRANSLATION_KEY_ESHOP_ERROR                      = 'eshop';
	public const TRANSLATION_KEY_WEIGHT_ERROR                     = 'weight';
	public const TRANSLATION_KEY_ADDRESS_ERROR                    = 'address';
	public const TRANSLATION_KEY_SIZE_ERROR                       = 'size';

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
	 * @var array $translations
	 */
	private $translations;

	/**
	 * Order constructor.
	 *
	 * @param Address $addressValidator Address validator.
	 * @param Size    $sizeValidator    Size validator.
	 * @param array   $translations     Translations with specified keys.
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
	 * @throws TranslationMissingException For the case required translation is not set.
	 */
	public function getValidationErrors( Entity\Order $order ): array {
		$errors = [];
		if ( ! $order->getNumber() ) {
			if ( empty( $this->translations[ self::TRANSLATION_KEY_NUMBER_ERROR ] ) ) {
				throw new TranslationMissingException( 'Order validator must have all translations set' );
			}
			$errors[] = $this->translations[ self::TRANSLATION_KEY_NUMBER_ERROR ];
		}
		if ( ! $order->getName() ) {
			if ( empty( $this->translations[ self::TRANSLATION_KEY_NAME_ERROR ] ) ) {
				throw new TranslationMissingException( 'Order validator must have all translations set' );
			}
			$errors[] = $this->translations[ self::TRANSLATION_KEY_NAME_ERROR ];
		}
		if ( ! $order->getValue() ) {
			if ( empty( $this->translations[ self::TRANSLATION_KEY_VALUE_ERROR ] ) ) {
				throw new TranslationMissingException( 'Order validator must have all translations set' );
			}
			$errors[] = $this->translations[ self::TRANSLATION_KEY_VALUE_ERROR ];
		}
		if ( ! $order->getPickupPointOrCarrierId() ) {
			if ( empty( $this->translations[ self::TRANSLATION_KEY_PICKUP_POINT_OR_CARRIER_ID_ERROR ] ) ) {
				throw new TranslationMissingException( 'Order validator must have all translations set' );
			}
			$errors[] = $this->translations[ self::TRANSLATION_KEY_PICKUP_POINT_OR_CARRIER_ID_ERROR ];
		}
		if ( ! $order->getEshop() ) {
			if ( empty( $this->translations[ self::TRANSLATION_KEY_ESHOP_ERROR ] ) ) {
				throw new TranslationMissingException( 'Order validator must have all translations set' );
			}
			$errors[] = $this->translations[ self::TRANSLATION_KEY_ESHOP_ERROR ];
		}
		if ( ! $this->validateFinalWeight( $order ) ) {
			if ( empty( $this->translations[ self::TRANSLATION_KEY_WEIGHT_ERROR ] ) ) {
				throw new TranslationMissingException( 'Order validator must have all translations set' );
			}
			$errors[] = $this->translations[ self::TRANSLATION_KEY_WEIGHT_ERROR ];
		}
		if ( ! $this->validateAddress( $order ) ) {
			if ( empty( $this->translations[ self::TRANSLATION_KEY_ADDRESS_ERROR ] ) ) {
				throw new TranslationMissingException( 'Order validator must have all translations set' );
			}
			$errors[] = $this->translations[ self::TRANSLATION_KEY_ADDRESS_ERROR ];
		}
		if ( ! $this->validateSize( $order ) ) {
			if ( empty( $this->translations[ self::TRANSLATION_KEY_SIZE_ERROR ] ) ) {
				throw new TranslationMissingException( 'Order validator must have all translations set' );
			}
			$errors[] = $this->translations[ self::TRANSLATION_KEY_SIZE_ERROR ];
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

}
