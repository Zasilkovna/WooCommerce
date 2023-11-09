<?php
/**
 * Class SharedOrderDetailsForm.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Order\Shared;

use Packetery\Module\FormFactory;
use Packetery\Module\FormValidators;
use Packetery\Core\Helper;
use Packetery\Nette\Forms\Form;

/**
 * Class SharedOrderDetailsForm.
 *
 * @package Packetery
 */
class SharedOrderDetailsFormFactory {

	public const FIELD_COD             = 'packetery_COD';
	public const FIELD_VALUE           = 'packetery_value';
	public const FIELD_DELIVER_ON      = 'packetery_deliver_on';
	public const FIELD_LENGTH          = 'packetery_length';
	public const FIELD_WIDTH           = 'packetery_width';
	public const FIELD_WEIGHT          = 'packetery_weight';
	public const FIELD_ADULT_CONTENT   = 'packetery_adult_content';
	public const FIELD_ORIGINAL_WEIGHT = 'packetery_original_weight';
	public const FIELD_HEIGHT          = 'packetery_height';

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
	 * @return Form
	 */
	public function create(): Form {
		$form = $this->formFactory->create();

		$form->addText( self::FIELD_WEIGHT, __( 'Weight (kg)', 'packeta' ) )
			->setRequired( false )
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
			->addRule( $form::FLOAT );
		$form->addText( self::FIELD_DELIVER_ON, __( 'Planned dispatch', 'packeta' ) )
			->setHtmlAttribute( 'class', 'date-picker' )
			->setHtmlAttribute( 'autocomplete', 'off' )
			->setRequired( false )
			// translators: %s: Represents minimal date for delayed delivery.
			->addRule( [ FormValidators::class, 'dateIsLater' ], __( 'Date must be later than %s', 'packeta' ), wp_date( Helper::DATEPICKER_FORMAT ) );

		return $form;
	}

	/**
	 * Setting default values
	 *
	 * @param Form              $form form.
	 * @param string|float|null $weight weight.
	 * @param string|float|null $originalWeight original weight.
	 * @param string|float|null $length length.
	 * @param string|float|null $width width.
	 * @param string|float|null $height height.
	 * @param string|float|null $COD Cash on delivery.
	 * @param string|float|null $orderValue Order value.
	 * @param string|bool|null  $adultContent Adult content.
	 * @param string|null       $deliverOn Estimated delivery date.
	 * @return void
	 */
	public function setDefaults(
		Form $form,
		$weight,
		$originalWeight,
		$length,
		$width,
		$height,
		$COD,
		$orderValue,
		$adultContent,
		$deliverOn
	): void {

		$form->setDefaults(
			[
				self::FIELD_WEIGHT          => $weight,
				self::FIELD_ORIGINAL_WEIGHT => $originalWeight,
				self::FIELD_LENGTH          => $length,
				self::FIELD_WIDTH           => $width,
				self::FIELD_HEIGHT          => $height,
				self::FIELD_COD             => $COD,
				self::FIELD_VALUE           => $orderValue,
				self::FIELD_ADULT_CONTENT   => $adultContent,
				self::FIELD_DELIVER_ON      => $deliverOn,
			]
		);
	}
}
