<?php

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\CoreHelper;
use Packetery\Core\Entity\Order;
use Packetery\Core\Validator;
use Packetery\Module\FormFactory;
use Packetery\Module\FormValidators;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Nette\Forms;

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
	 * @var FormFactory
	 */
	private $formFactory;

	/**
	 * @var OptionsProvider
	 */
	private $options;

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * FormFactory constructor
	 *
	 * @param FormFactory     $formFactory Form factory.
	 * @param OptionsProvider $options Options provider.
	 */
	public function __construct( FormFactory $formFactory, OptionsProvider $options, WpAdapter $wpAdapter ) {
		$this->formFactory = $formFactory;
		$this->options     = $options;
		$this->wpAdapter   = $wpAdapter;
	}

	public function create(): Forms\Form {
		$form = $this->formFactory->create();
		$unit = $this->options->getDimensionsUnit();

		$form->addText( self::FIELD_WEIGHT, $this->wpAdapter->__( 'Weight (kg)', 'packeta' ) )
			->setRequired( false )
			->setNullable()
			->addRule( Forms\Form::FLOAT, $this->wpAdapter->__( 'The weight must be a number.', 'packeta' ) )
			->addRule( Forms\Form::MIN, $this->wpAdapter->__( 'The weight must be a positive number.', 'packeta' ), 0 );
		$form->addHidden( self::FIELD_ORIGINAL_WEIGHT );
		$this->formFactory->addDimension( $form, self::FIELD_LENGTH, (string) $this->wpAdapter->__( 'Length', 'packeta' ), $unit )
			->setNullable();
		$this->formFactory->addDimension( $form, self::FIELD_WIDTH, (string) $this->wpAdapter->__( 'Width', 'packeta' ), $unit )
			->setNullable();
		$this->formFactory->addDimension( $form, self::FIELD_HEIGHT, (string) $this->wpAdapter->__( 'Height', 'packeta' ), $unit )
			->setNullable();
		$form->addCheckbox( self::FIELD_ADULT_CONTENT, $this->wpAdapter->__( 'Adult content', 'packeta' ) )
			->setRequired( false );
		$form->addText( self::FIELD_COD, $this->wpAdapter->__( 'Cash on delivery', 'packeta' ) )
			->setRequired( false )
			->setNullable()
			->addRule( Forms\Form::FLOAT );
		$form->addHidden( self::FIELD_CALCULATED_COD );
		$form->addText( self::FIELD_VALUE, $this->wpAdapter->__( 'Order value', 'packeta' ) )
			->setRequired( false )
			->setNullable()
			->addRule( Forms\Form::FLOAT );
		$form->addHidden( self::FIELD_CALCULATED_VALUE );
		$form->addText( self::FIELD_DELIVER_ON, $this->wpAdapter->__( 'Planned dispatch', 'packeta' ) )
			->setHtmlAttribute( 'class', 'date-picker' )
			->setHtmlAttribute( 'autocomplete', 'off' )
			->setRequired( false )
			->setNullable()
			// translators: %s: Represents minimal date for delayed delivery.
			->addRule( [ FormValidators::class, 'dateIsLater' ], $this->wpAdapter->__( 'Date must be later than %s', 'packeta' ), wp_date( CoreHelper::DATEPICKER_FORMAT ) );

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
	public function getInvalidFieldsFromValidationResult( array $validationResult ): array {
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

	/**
	 * @param string[] $invalidFieldNames
	 *
	 * @return string
	 */
	public function getInvalidFieldsMessageFromValidationResult( array $invalidFieldNames, Order $order ): string {
		$fieldTranslations = [
			self::FIELD_VALUE  => $this->wpAdapter->__( 'value', 'packeta' ),
			self::FIELD_WEIGHT => $this->wpAdapter->__( 'weight', 'packeta' ),
			self::FIELD_HEIGHT => $this->wpAdapter->__( 'height', 'packeta' ),
			self::FIELD_WIDTH  => $this->wpAdapter->__( 'width', 'packeta' ),
			self::FIELD_LENGTH => $this->wpAdapter->__( 'length', 'packeta' ),
		];
		$message           = '';
		$invalidFields     = [];

		foreach ( $invalidFieldNames as $fieldName ) {
			if ( isset( $fieldTranslations[ $fieldName ] ) ) {
				$invalidFields[] = $fieldTranslations[ $fieldName ];
			}
		}

		if ( $invalidFields !== [] ) {
			$invalidFieldsString = implode( ', ', $invalidFields );

			$message = sprintf(
				// translators: %s: Required fields.
				(string) $this->wpAdapter->__( 'Please fill in all required shipment details (%s) before submitting.', 'packeta' ),
				$invalidFieldsString
			);
		}

		if ( $order->hasToFillCustomsDeclaration() ) {
			$message .= " {$this->wpAdapter->__( 'Customs declaration has to be filled in order detail.', 'packeta' )}";
		}

		return $message;
	}
}
