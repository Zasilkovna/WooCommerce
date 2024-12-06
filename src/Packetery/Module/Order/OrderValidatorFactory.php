<?php
/**
 * Class ValidatorTranslationsInterface
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Validator;
use Packetery\Core\Validator\Address;
use Packetery\Core\Validator\Size;

/**
 * Class ValidatorTranslationsInterface
 *
 * @package Packetery
 */
class OrderValidatorFactory {

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
	 * Translations.
	 *
	 * @var ValidatorTranslations
	 */
	private $validatorTranslations;

	/**
	 * Order constructor.
	 *
	 * @param Address               $addressValidator      Address validator.
	 * @param Size                  $sizeValidator         Size validator.
	 * @param ValidatorTranslations $validatorTranslations Translations.
	 */
	public function __construct(
		Address $addressValidator,
		Size $sizeValidator,
		ValidatorTranslations $validatorTranslations
	) {
		$this->addressValidator      = $addressValidator;
		$this->sizeValidator         = $sizeValidator;
		$this->validatorTranslations = $validatorTranslations;
	}

	/**
	 * Creates validator.
	 */
	public function create(): Validator\Order {
		return new Validator\Order( $this->addressValidator, $this->sizeValidator, $this->validatorTranslations );
	}
}
