<?php
/**
 * Class Form.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\CoreHelper;
use Packetery\Core\Validator;
use Packetery\Module\FormFactory;
use Packetery\Module\FormValidators;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Nette\Forms;

/**
 * Class Form.
 *
 * @package Packetery
 */
class Form {

	public const FIELD_WEIGHT           = 'packeteryWeight';
	public const FIELD_ORIGINAL_WEIGHT  = 'packeteryOriginalWeight';
	public const FIELD_VALUE            = 'packeteryValue';
	public const FIELD_CALCULATED_VALUE = 'packeteryCalculatedValue';
	public const FIELD_LENGTH           = 'packeteryLength';
	public const FIELD_WIDTH            = 'packeteryWidth';
	public const FIELD_HEIGHT           = 'packeteryHeight';
	public const FIELD_ADULT_CONTENT    = 'packeteryAdultContent';
	public const FIELD_COD              = 'packeteryCOD';
	public const FIELD_CALCULATED_COD   = 'packeteryCalculatedCod';
	public const FIELD_DELIVER_ON       = 'packeteryDeliverOn';

	/**
	 * Class FormFactory
	 *
	 * @var FormFactory
	 */
	private $formFactory;

	/**
	 * Class Provider
	 *
	 * @var OptionsProvider
	 */
	private $options;

	/**
	 * FormFactory constructor
	 *
	 * @param FormFactory     $formFactory Form factory.
	 * @param OptionsProvider $options Options provider.
	 */
	public function __construct( FormFactory $formFactory, OptionsProvider $options ) {
		$this->formFactory = $formFactory;
		$this->options     = $options;
	}

	public function create(): Forms\Form {
		$form = $this->formFactory->create();
		$unit = $this->options->getDimensionsUnit();

		$form->addText( self::FIELD_WEIGHT, __( 'Weight (kg)', 'packeta' ) )
			->setRequired( false )
			->setNullable()
			->addRule( Forms\Form::FLOAT );
		$form->addHidden( self::FIELD_ORIGINAL_WEIGHT );
		$this->formFactory->addDimension( $form, self::FIELD_LENGTH, __( 'Length', 'packeta' ), $unit )
			->setNullable();
		$this->formFactory->addDimension( $form, self::FIELD_WIDTH, __( 'Width', 'packeta' ), $unit )
			->setNullable();
		$this->formFactory->addDimension( $form, self::FIELD_HEIGHT, __( 'Height', 'packeta' ), $unit )
			->setNullable();
		$form->addCheckbox( self::FIELD_ADULT_CONTENT, __( 'Adult content', 'packeta' ) )
			->setRequired( false );
		$form->addText( self::FIELD_COD, __( 'Cash on delivery', 'packeta' ) )
			->setRequired( false )
			->setNullable()
			->addRule( Forms\Form::FLOAT );
		$form->addHidden( self::FIELD_CALCULATED_COD );
		$form->addText( self::FIELD_VALUE, __( 'Order value', 'packeta' ) )
			->setRequired( false )
			->setNullable()
			->addRule( Forms\Form::FLOAT );
		$form->addHidden( self::FIELD_CALCULATED_VALUE );
		$form->addText( self::FIELD_DELIVER_ON, __( 'Planned dispatch', 'packeta' ) )
			->setHtmlAttribute( 'class', 'date-picker' )
			->setHtmlAttribute( 'autocomplete', 'off' )
			->setRequired( false )
			->setNullable()
			// translators: %s: Represents minimal date for delayed delivery.
			->addRule( [ FormValidators::class, 'dateIsLater' ], __( 'Date must be later than %s', 'packeta' ), wp_date( CoreHelper::DATEPICKER_FORMAT ) );

		return $form;
	}

	public function setDefaults(
		Forms\Form $form,
		?float $weight,
		?float $originalWeight,
		?float $length,
		?float $width,
		?float $height,
		?float $cod,
		?float $calculatedCod,
		?float $orderValue,
		?float $calculatedValue,
		?bool $adultContent,
		?string $deliverOn
	): void {

		$form->setDefaults(
			[
				self::FIELD_WEIGHT           => $weight,
				self::FIELD_ORIGINAL_WEIGHT  => $originalWeight,
				self::FIELD_LENGTH           => $length,
				self::FIELD_WIDTH            => $width,
				self::FIELD_HEIGHT           => $height,
				self::FIELD_COD              => $cod,
				self::FIELD_CALCULATED_COD   => $calculatedCod,
				self::FIELD_VALUE            => $orderValue,
				self::FIELD_CALCULATED_VALUE => $calculatedValue,
				self::FIELD_ADULT_CONTENT    => $adultContent,
				self::FIELD_DELIVER_ON       => $deliverOn,
			]
		);
	}

	/**
	 * Gets invalid fields from validation result.
	 *
	 * @param array<string, string> $validationResult Validation result.
	 *
	 * @return string[]
	 */
	public static function getInvalidFieldsFromValidationResult( array $validationResult ): array {
		$validationFormInputMapping = [
			self::FIELD_VALUE  => Validator\Order::ERROR_TRANSLATION_KEY_VALUE,
			self::FIELD_WEIGHT => Validator\Order::ERROR_TRANSLATION_KEY_WEIGHT,
			self::FIELD_HEIGHT => Validator\Order::ERROR_TRANSLATION_KEY_HEIGHT,
			self::FIELD_WIDTH  => Validator\Order::ERROR_TRANSLATION_KEY_WIDTH,
			self::FIELD_LENGTH => Validator\Order::ERROR_TRANSLATION_KEY_LENGTH,
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
