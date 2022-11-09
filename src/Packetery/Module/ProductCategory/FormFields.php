<?php
/**
 * Packetery product category form fields.
 *
 * @package Packetery
 */

declare( strict_types=1 );


namespace Packetery\Module\ProductCategory;

use Packetery\Module\FormFactory;
use Packetery\Module\ProductCategory;
use PacketeryLatte\Engine;
use PacketeryNette\Forms\Form;
use Packetery\Module\Checkout;

/**
 * Class FormFields
 *
 * @package Packetery
 */
class FormFields {

	/**
	 * Form factory.
	 *
	 * @var FormFactory
	 */
	private $formFactory;

	/**
	 * Latte engine.
	 *
	 * @var Engine
	 */
	private $latteEngine;

	/**
	 * Checkout.
	 *
	 * @var Checkout
	 */
	private $checkout;

	/**
	 * Tab constructor.
	 *
	 * @param FormFactory $formFactory Factory engine.
	 * @param Engine      $latteEngine Latte engine.
	 * @param Checkout    $checkout    Checkout.
	 */
	public function __construct( FormFactory $formFactory, Engine $latteEngine, Checkout $checkout ) {
		$this->formFactory = $formFactory;
		$this->latteEngine = $latteEngine;
		$this->checkout    = $checkout;
	}

	/**
	 * Register component.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'product_cat_edit_form_fields', [ $this, 'render' ], 20, 1 );
		add_action( 'product_cat_add_form_fields', [ $this, 'render' ], 20 );
		add_action( 'edit_term', [ $this, 'saveData' ], 10, 3 );
		add_action( 'created_term', [ $this, 'saveData' ], 10, 3 );
	}

	/**
	 * Creates form instance.
	 *
	 * @param ProductCategory\Entity|null $category Product category entity.
	 *
	 * @return Form
	 */
	private function createForm( ?ProductCategory\Entity $category ): Form {
		$form = $this->formFactory->create();

		$shippingRatesContainer = $form->addContainer( ProductCategory\Entity::META_DISALLOWED_SHIPPING_RATES );
		$shippingRates          = $this->checkout->getAllShippingRates();

		foreach ( $shippingRates as $shippingRate ) {
			$shippingRatesContainer->addCheckbox( $shippingRate['id'], $shippingRate['label'] );
		}

		$form->setDefaults(
			[
				ProductCategory\Entity::META_DISALLOWED_SHIPPING_RATES => null !== $category ? $category->getDisallowedShippingRates() : [],
			]
		);

		return $form;
	}

	/**
	 * Renders tab.
	 *
	 * @param mixed $term Term (category) being edited.
	 *
	 * @return void
	 */
	public function render( $term = null ): void {
		$entity = isset( $term->term_id ) ? Entity::fromTermId( (int) $term->term_id ) : null;
		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/product_category/form-fields.latte',
			[
				'form'         => $this->createForm( $entity ),
				'translations' => [
					'disallowedShippingRatesHeading' => __( 'Packeta shipping methods disabled for this category', 'packeta' ),
				],
			]
		);
	}

	/**
	 * Saves product category data.
	 *
	 * @param int    $termId         Post ID.
	 * @param int    $termTaxonomyId Term taxonomy ID.
	 * @param string $taxonomy       Taxonomy slug.
	 *
	 * @return void
	 */
	public function saveData( int $termId, int $termTaxonomyId, string $taxonomy = '' ): void {
		if ( ProductCategory\Entity::TAXONOMY_NAME !== $taxonomy ) {
			return;
		}
		$productCategory = ProductCategory\Entity::fromTermId( $termId );
		$form            = $this->createForm( $productCategory );

		$form->onSuccess[] = function ( Form $form, array $values ) use ( $productCategory ): void {
			$this->processFormData( $productCategory->getId(), $values );
		};

		if ( $form->isSubmitted() ) {
			$form->fireEvents();
		}
	}

	/**
	 * Process form data.
	 *
	 * @param int   $categoryId Product ID.
	 * @param array $values     Form values.
	 */
	public function processFormData( int $categoryId, array $values ): void {
		foreach ( $values as $attr => $value ) {
			if ( is_bool( $value ) ) {
				$value = $value ? '1' : '0';
			}

			if ( ProductCategory\Entity::META_DISALLOWED_SHIPPING_RATES === $attr ) {
				$value = array_filter( $value );
			}

			update_term_meta( $categoryId, $attr, $value );
		}
	}
}
