<?php

declare( strict_types=1 );

namespace Packetery\Module\Forms;

use Packetery\Core\CoreHelper;
use Packetery\Module\Carrier\Options;
use Packetery\Module\Carrier\OptionsPage;
use Packetery\Module\Carrier\ShippingClassPage;
use Packetery\Module\FormValidators;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Nette\Forms\Container;
use Packetery\Nette\Forms\Controls\SelectBox;
use Packetery\Nette\Forms\Form;

class ShippingFormHelper {

	/**
	 * @var array<int, array<string, string|int>>|null
	 */
	private static ?array $shippingClasses = null;
	private WpAdapter $wpAdapter;

	public function __construct( WpAdapter $wpAdapter ) {
		$this->wpAdapter = $wpAdapter;
	}

	/**
	 * Creates container id for given field.
	 *
	 * @param Form   $form Form.
	 * @param string $field Field name.
	 *
	 * @return string
	 */
	public function createFieldContainerId( Form $form, string $field ): string {
		return sprintf( '%s_%s_containerId', $form->getName(), $field );
	}

	/**
	 * @param Container  $weightLimits
	 * @param int|string $index
	 *
	 * @throws \RuntimeException
	 */
	public function addWeightLimit( Container $weightLimits, $index, SelectBox $pricingTypeComponent ): void {
		$form = $weightLimits->getForm();
		if ( $form === null ) {
			throw new \RuntimeException( 'Form is not attached to the container.' );
		}

		$limit       = $weightLimits->addContainer( (string) $index );
		$weightField = $limit->addText( 'weight', __( 'Weight up to', 'packeta' ) . ':' );
		$weightRules = $weightField->addConditionOn( $pricingTypeComponent, Form::EQUAL, Options::PRICING_TYPE_BY_WEIGHT );
		$weightRules->setRequired();
		$weightRules->addRule( Form::FLOAT, __( 'Please enter a valid decimal number.', 'packeta' ) );
		$weightRules->addRule( Form::MIN, null, 0 );
		// translators: %d is numeric threshold.
		$weightRules->addRule( [ FormValidators::class, 'greaterThan' ], __( 'Enter number greater than %d', 'packeta' ), 0.0 );

		$weightRules->addFilter(
			function ( float $value ) {
				return CoreHelper::simplifyWeight( $value );
			}
		);

		$priceField = $limit->addText( 'price', __( 'Price', 'packeta' ) . ':' );
		$priceRules = $priceField->addConditionOn( $pricingTypeComponent, Form::EQUAL, Options::PRICING_TYPE_BY_WEIGHT );
		$priceRules->setRequired();
		$priceRules->addRule( Form::FLOAT, __( 'Please enter a valid decimal number.', 'packeta' ) );
		$priceRules->addRule( Form::MIN, null, 0 );
		// translators: %d is numeric threshold.
		$priceRules->addRule( [ FormValidators::class, 'greaterThan' ], __( 'Enter number greater than %d', 'packeta' ), 0.0 );
	}

	/**
	 * @param Container  $productValueLimits
	 * @param int|string $index
	 *
	 * @throws \RuntimeException
	 */
	public function addProductValueLimit( Container $productValueLimits, $index, SelectBox $pricingTypeComponent ): void {
		$form = $productValueLimits->getForm();
		if ( $form === null ) {
			throw new \RuntimeException( 'Form is not attached to the container.' );
		}

		$limit = $productValueLimits->addContainer( $index );

		$valueField = $limit->addText( 'value', __( 'Product value up to', 'packeta' ) . ':' );
		$valueRules = $valueField->addConditionOn( $pricingTypeComponent, Form::EQUAL, Options::PRICING_TYPE_BY_PRODUCT_VALUE );
		$valueRules->setRequired();
		$valueRules->addRule( Form::FLOAT, __( 'Please enter a valid decimal number.', 'packeta' ) );
		// translators: %d is numeric threshold.
		$valueRules->addRule( Form::MIN, null, 0.0 );
		// translators: %d is numeric threshold.
		$valueRules->addRule( [ FormValidators::class, 'greaterThan' ], __( 'Enter number greater than %d', 'packeta' ), 0.0 );

		$priceField = $limit->addText( 'price', __( 'Price', 'packeta' ) . ':' );
		$priceRules = $priceField->addConditionOn( $pricingTypeComponent, Form::EQUAL, Options::PRICING_TYPE_BY_PRODUCT_VALUE );
		$priceRules->setRequired();
		$priceRules->addRule( Form::FLOAT, __( 'Please enter a valid decimal number.', 'packeta' ) );
		// translators: %d is numeric threshold.
		$priceRules->addRule( Form::MIN, null, 0.0 );
	}

	/**
	 * Adds limit fields to form.
	 *
	 * @param Container  $surchargeLimits Container.
	 * @param int|string $index Index.
	 *
	 * @return void
	 */
	public function addSurchargeLimit( Container $surchargeLimits, $index ): void {
		$limit = $surchargeLimits->addContainer( (string) $index );
		$item  = $limit->addText( 'order_price', __( 'Order price up to', 'packeta' ) . ':' );
		$item->setRequired();
		$item->addRule( Form::FLOAT, __( 'Please enter a valid decimal number.', 'packeta' ) );
		$item->addRule( Form::MIN, null, 0 );
		$item->addCondition( Form::MAX, 0 )
			->addCondition( Form::MIN, 0 )
			// translators: %d is the value.
			->addRule( Form::BLANK, __( 'Value must not be %d', 'packeta' ), 0 );

		$item = $limit->addText( 'surcharge', __( 'Surcharge', 'packeta' ) . ':' );
		$item->setRequired();
		$item->addRule( Form::FLOAT, __( 'Please enter a valid decimal number.', 'packeta' ) );
		$item->addRule( Form::MIN, null, 0 );
	}

	/**
	 * @return array<int, array<string, string|int>>
	 */
	public function getShippingClasses(): array {
		if ( self::$shippingClasses !== null ) {
			return self::$shippingClasses;
		}
		self::$shippingClasses = [];

		$terms = $this->wpAdapter->getTerms(
			[
				'taxonomy'   => 'product_shipping_class',
				'hide_empty' => false,
			]
		);
		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( isset( $term->slug, $term->name ) ) {
					self::$shippingClasses[] = [
						'slug'    => (string) $term->slug,
						'name'    => (string) $term->name,
						// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
						'term_id' => (int) $term->term_id,
					];
				}
			}
		}

		return self::$shippingClasses;
	}

	/**
	 * Transforms new_ keys to common numeric.
	 *
	 * @param array<string, mixed> $options Options to merge.
	 * @param string               $limitsContainer Container id.
	 *
	 * @return array<string, mixed>
	 */
	public function mergeNewLimits( array $options, string $limitsContainer ): array {
		$newOptions = [];
		if ( isset( $options[ $limitsContainer ] ) && is_array( $options[ $limitsContainer ] ) ) {
			foreach ( $options[ $limitsContainer ] as $key => $option ) {
				if ( is_int( $key ) ) {
					$newOptions[ $key ] = $option;
				}
				if ( strpos( (string) $key, 'new_' ) === 0 ) {
					$newOptions[] = $option;
				}
			}
			$options[ $limitsContainer ] = $newOptions;
		}

		return $options;
	}

	/**
	 * @param Form                 $form Form.
	 * @param array<string, mixed> $options Form data.
	 * @param string               $limitsContainer Container id.
	 * @param string               $limitKey Rule id.
	 * @param string               $overlappingMessage Error message.
	 */
	public function checkOverlapping( Form $form, array $options, string $limitsContainer, string $limitKey, string $overlappingMessage ): void {
		if ( ! is_array( $options[ $limitsContainer ] ) ) {
			$options[ $limitsContainer ] = [];
		}
		$limits = array_column( $options[ $limitsContainer ], $limitKey );
		if ( count( array_unique( $limits, SORT_NUMERIC ) ) !== count( $limits ) ) {
			add_settings_error( $limitsContainer, $limitsContainer, esc_attr( $overlappingMessage ) );
			$form->addError( $overlappingMessage );
		}
	}

	/**
	 * @param array<string, mixed> $options Form data.
	 * @param string               $limitsContainer Container id.
	 * @param string               $limitKey Rule id.
	 *
	 * @return array<string, mixed>
	 */
	public function sortLimits( array $options, string $limitsContainer, string $limitKey ): array {
		if ( ! is_array( $options[ $limitsContainer ] ) ) {
			$options[ $limitsContainer ] = [];
		}
		$limits = array_column( $options[ $limitsContainer ], $limitKey );
		array_multisort( $limits, SORT_ASC, $options[ $limitsContainer ] );

		return $options;
	}

	public function createUrl(
		?string $countryCode = null,
		?string $carrierId = null,
		?string $classSlug = null
	): string {
		$params = [
			'page' => OptionsPage::SLUG,
		];

		if ( $countryCode !== null ) {
			$params[ OptionsPage::PARAMETER_COUNTRY_CODE ] = $countryCode;
		}
		if ( $carrierId !== null ) {
			$params[ OptionsPage::PARAMETER_CARRIER_ID ] = $carrierId;
		}
		if ( $classSlug !== null ) {
			$params[ ShippingClassPage::PARAMETER_CLASS_ID ] = $classSlug;
		}

		return add_query_arg(
			$params,
			admin_url( 'admin.php' )
		);
	}
}
