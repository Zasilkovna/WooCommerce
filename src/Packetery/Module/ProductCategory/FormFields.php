<?php
/**
 * Packetery product category form fields.
 *
 * @package Packetery
 */

declare( strict_types=1 );


namespace Packetery\Module\ProductCategory;

use Packetery\Module\Carrier\CarDeliveryConfig;
use Packetery\Module\Carrier\EntityRepository;
use Packetery\Module\Carrier\OptionPrefixer;
use Packetery\Module\FormFactory;
use Packetery\Module\ProductCategory;
use Packetery\Latte\Engine;
use Packetery\Nette\Forms\Form;

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
	 * Carrier repository.
	 *
	 * @var EntityRepository
	 */
	private $carrierRepository;

	/**
	 * Car delivery config
	 *
	 * @var CarDeliveryConfig
	 */
	private $carDeliveryConfig;

	/**
	 * Product category entity factory.
	 *
	 * @var ProductCategoryEntityFactory
	 */
	private $productCategoryEntityFactory;

	/**
	 * Tab constructor.
	 *
	 * @param FormFactory                  $formFactory       Factory engine.
	 * @param Engine                       $latteEngine       Latte engine.
	 * @param EntityRepository             $carrierRepository Carrier repository.
	 * @param CarDeliveryConfig            $carDeliveryConfig Car delivery config.
	 * @param ProductCategoryEntityFactory $productCategoryEntityFactory Product category entity factory.
	 */
	public function __construct(
		FormFactory $formFactory,
		Engine $latteEngine,
		EntityRepository $carrierRepository,
		CarDeliveryConfig $carDeliveryConfig,
		ProductCategoryEntityFactory $productCategoryEntityFactory
	) {
		$this->formFactory                  = $formFactory;
		$this->latteEngine                  = $latteEngine;
		$this->carrierRepository            = $carrierRepository;
		$this->carDeliveryConfig            = $carDeliveryConfig;
		$this->productCategoryEntityFactory = $productCategoryEntityFactory;
	}

	/**
	 * Register component.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'product_cat_edit_form_fields', [ $this, 'render' ], 20, 1 );
		add_action( 'product_cat_add_form_fields', [ $this, 'render' ], 20 );
		add_action( 'edit_term', [ $this, 'saveData' ], 10, 3 );
		add_action( 'created_term', [ $this, 'saveData' ], 10, 3 );
	}

	/**
	 * Creates form instance.
	 *
	 * @param ProductCategory\Entity|null $productCategory Product category entity.
	 *
	 * @return Form
	 */
	private function createForm( ?ProductCategory\Entity $productCategory ): Form {
		$form = $this->formFactory->create();

		$shippingRatesContainer = $form->addContainer( ProductCategory\Entity::META_DISALLOWED_SHIPPING_RATES );
		$carriersList           = $this->carrierRepository->getAllActiveCarriersList();

		foreach ( $carriersList as $carrier ) {
			if ( $this->carDeliveryConfig->isCarDeliveryCarrierDisabled( OptionPrefixer::removePrefix( $carrier['option_id'] ) ) ) {
				continue;
			}

			$shippingRatesContainer->addCheckbox( $carrier['option_id'], $carrier['label'] );
		}

		$form->setDefaults(
			[
				ProductCategory\Entity::META_DISALLOWED_SHIPPING_RATES => $productCategory ? $productCategory->getDisallowedShippingRateChoices() : [],
			]
		);

		return $form;
	}

	/**
	 * Renders tab.
	 *
	 * @param \WP_Term|string $term Term (category) being edited.
	 *
	 * @return void
	 */
	public function render( $term ): void {
		$isProductCategoryObject = ( is_object( $term ) && get_class( $term ) === \WP_Term::class );
		$productCategory         = $isProductCategoryObject ? $this->productCategoryEntityFactory->fromTermId( $term->term_id ) : null;
		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/product_category/form-fields.latte',
			[
				'form'         => $this->createForm( $productCategory ),
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
		$productCategory = $this->productCategoryEntityFactory->fromTermId( $termId );
		$form            = $this->createForm( $productCategory );

		$form->onSuccess[] = function ( Form $form, array $shippingRates ) use ( $productCategory ): void {
			if ( isset( $shippingRates[ ProductCategory\Entity::META_DISALLOWED_SHIPPING_RATES ] ) ) {
				$disallowedShippingRates = array_filter( $shippingRates[ ProductCategory\Entity::META_DISALLOWED_SHIPPING_RATES ] );
				update_term_meta( $productCategory->getId(), Entity::META_DISALLOWED_SHIPPING_RATES, $disallowedShippingRates );
			}
		};

		if ( $form->isSubmitted() ) {
			$form->fireEvents();
		}
	}
}
