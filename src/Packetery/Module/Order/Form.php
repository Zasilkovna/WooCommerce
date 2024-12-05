<?php
/**
 * Class Form.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Validator\Order;
use Packetery\Module\FormFactory;
use Packetery\Module\FormValidators;
use Packetery\Core\CoreHelper;
use Packetery\Nette\Forms;

/**
 * Class Form.
 *
 * @package Packetery
 */
class Form {

	public const FIELD_WEIGHT          = 'packetery_weight';
	public const FIELD_ORIGINAL_WEIGHT = 'packetery_original_weight';
	public const FIELD_VALUE           = 'packetery_value';
	public const FIELD_LENGTH          = 'packetery_length';
	public const FIELD_WIDTH           = 'packetery_width';
	public const FIELD_HEIGHT          = 'packetery_height';
	public const FIELD_ADULT_CONTENT   = 'packetery_adult_content';
	public const FIELD_COD             = 'packetery_COD';
	public const FIELD_DELIVER_ON      = 'packetery_deliver_on';

	/**
	 * Class FormFactory
	 *
	 * @var FormFactory
	 */
	private $formFactory;

	/**
	 * FormFactory constructor
	 *
	 * @param FormFactory $formFactory Form factory.
	 */
	public function __construct( FormFactory $formFactory ) {
		$this->formFactory = $formFactory;
	}

	/**
	 * Creating a form
	 *
	 * @return Forms\Form
	 */
	public function create(): Forms\Form {
		$form = $this->formFactory->create();

		$form->addText( self::FIELD_WEIGHT, __( 'Weight (kg)', 'packeta' ) )
			->setRequired( false )
			->setNullable()
			->addRule( $form::FLOAT, __( 'Provide numeric value!', 'packeta' ) );
		$form->addHidden( self::FIELD_ORIGINAL_WEIGHT );
		$form->addText( self::FIELD_WIDTH, __( 'Width (mm)', 'packeta' ) )
			->setRequired( false )
			->setNullable()
			->addRule( $form::FLOAT, __( 'Provide numeric value!', 'packeta' ) );
		$form->addText( self::FIELD_LENGTH, __( 'Length (mm)', 'packeta' ) )
			->setRequired( false )
			->setNullable()
			->addRule( $form::FLOAT, __( 'Provide numeric value!', 'packeta' ) );
		$form->addText( self::FIELD_HEIGHT, __( 'Height (mm)', 'packeta' ) )
			->setRequired( false )
			->setNullable()
			->addRule( $form::FLOAT, __( 'Provide numeric value!', 'packeta' ) );
		$form->addCheckbox( self::FIELD_ADULT_CONTENT, __( 'Adult content', 'packeta' ) )
			->setRequired( false );
		$form->addText( self::FIELD_COD, __( 'Cash on delivery', 'packeta' ) )
			->setRequired( false )
			->setNullable()
			->addRule( $form::FLOAT );
		$form->addText( self::FIELD_VALUE, __( 'Order value', 'packeta' ) )
			->setRequired( false )
			->setNullable()
			->addRule( $form::FLOAT );
		$form->addText( self::FIELD_DELIVER_ON, __( 'Planned dispatch', 'packeta' ) )
			->setHtmlAttribute( 'class', 'date-picker' )
			->setHtmlAttribute( 'autocomplete', 'off' )
			->setRequired( false )
			->setNullable()
			// translators: %s: Represents minimal date for delayed delivery.
			->addRule( [ FormValidators::class, 'dateIsLater' ], __( 'Date must be later than %s', 'packeta' ), wp_date( CoreHelper::DATEPICKER_FORMAT ) );

		return $form;
	}

	/**
	 * Setting default values
	 *
	 * @param Forms\Form  $form form.
	 * @param float|null  $weight weight.
	 * @param float|null  $originalWeight Original weight.
	 * @param float|null  $length length.
	 * @param float|null  $width width.
	 * @param float|null  $height height.
	 * @param float|null  $cod Cash on delivery.
	 * @param float|null  $orderValue Order value.
	 * @param bool|null   $adultContent Allows adult content.
	 * @param string|null $deliverOn Estimated date of delivery.
	 */
	public function setDefaults(
		Forms\Form $form,
		?float $weight,
		?float $originalWeight,
		?float $length,
		?float $width,
		?float $height,
		?float $cod,
		?float $orderValue,
		?bool $adultContent,
		?string $deliverOn
	): void {

		$form->setDefaults(
			[
				self::FIELD_WEIGHT          => $weight,
				self::FIELD_ORIGINAL_WEIGHT => $originalWeight,
				self::FIELD_LENGTH          => $length,
				self::FIELD_WIDTH           => $width,
				self::FIELD_HEIGHT          => $height,
				self::FIELD_COD             => $cod,
				self::FIELD_VALUE           => $orderValue,
				self::FIELD_ADULT_CONTENT   => $adultContent,
				self::FIELD_DELIVER_ON      => $deliverOn,
			]
		);
	}

	/**
	 * Gets invalid fields from validation result.
	 *
	 * @param array<string, string> $validationResult Validation result.
	 *
	 * @return array
	 */
	public static function getInvalidFieldsFromValidationResult( array $validationResult ): array {
		$validationFormInputMapping = [
			self::FIELD_VALUE  => Order::ERROR_TRANSLATION_KEY_VALUE,
			self::FIELD_WEIGHT => Order::ERROR_TRANSLATION_KEY_WEIGHT,
			self::FIELD_HEIGHT => Order::ERROR_TRANSLATION_KEY_HEIGHT,
			self::FIELD_WIDTH  => Order::ERROR_TRANSLATION_KEY_WIDTH,
			self::FIELD_LENGTH => Order::ERROR_TRANSLATION_KEY_LENGTH,
		];

		$fields = [];
		foreach ( $validationFormInputMapping as $fieldName => $value ) {
			if ( ! isset( $validationResult[ $value ] ) ) {
				continue;
			}

			$fields[] = $fieldName;
		}

		return $fields;
	}
}
