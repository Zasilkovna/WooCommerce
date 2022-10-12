<?php
/**
 * Class Order
 *
 * @package Packetery\Validator
 */

declare( strict_types=1 );

namespace Packetery\Core\Validator;

use Packetery\Core\Entity;
use Packetery\Core\ValidationResult;

/**
 * Class Order
 *
 * @package Packetery\Validator
 */
class Order {

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
	 * Validates data needed to submit packet.
	 *
	 * @param Entity\Order $order Order entity.
	 *
	 * @return ValidationResult
	 */
	public function validateForSubmission( Entity\Order $order ): ValidationResult {
		$report = new ValidationResult();

		if ( null !== $order->getPacketId() ) {
			$report->addError( __( 'Already submitted', 'packeta' ) );
			return $report;
		}

		if ( false === $this->validateFinalWeight( $order ) ) {
			$report->addError( __( 'Valid weight is missing', 'packeta' ) );
		}

		$carrier = $order->getCarrier();
		if ( null !== $carrier && $carrier->requiresSize() && false === $this->validateSize( $order ) ) {
			$report->addError( __( 'Valid size is missing', 'packeta' ) );
		}

		return $report;
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
	public function validateFinalWeight( Entity\Order $order ): bool {
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
		$carrier = $order->getCarrier();
		if ( null === $carrier ) {
			return true;
		}
		if ( $carrier->requiresSize() ) {
			$size = $order->getSize();
			if ( null === $size ) {
				return false;
			}

			return $this->sizeValidator->validate( $size );
		}

		return true;
	}
}
