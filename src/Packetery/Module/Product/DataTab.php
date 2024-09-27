<?php
/**
 * Packetery product tab.
 *
 * @package Packetery\Module\Product
 */

declare( strict_types=1 );


namespace Packetery\Module\Product;

use Packetery\Module\Carrier\CarDeliveryConfig;
use Packetery\Module\Carrier\EntityRepository;
use Packetery\Module\Carrier\OptionPrefixer;
use Packetery\Module\FormFactory;
use Packetery\Module\Product;
use Packetery\Latte\Engine;
use Packetery\Nette\Forms\Form;

/**
 * Class Tab
 *
 * @package Packetery\Module\Product
 */
class DataTab {

	const NAME = 'packetery-tab';

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
	 * Carrier repository
	 *
	 * @var EntityRepository
	 */
	private $carrierRepository;

	/**
	 * Car delivery config.
	 *
	 * @var CarDeliveryConfig
	 */
	private $carDeliveryConfig;

	/**
	 * Product entity factory.
	 *
	 * @var ProductEntityFactory
	 */
	private $productEntityFactory;

	/**
	 * Tab constructor.
	 *
	 * @param FormFactory          $formFactory          Factory engine.
	 * @param Engine               $latteEngine          Latte engine.
	 * @param EntityRepository     $carrierRepository    Carrier repository.
	 * @param CarDeliveryConfig    $carDeliveryConfig    Car Delivery config.
	 * @param ProductEntityFactory $productEntityFactory Product entity factory.
	 */
	public function __construct(
		FormFactory $formFactory,
		Engine $latteEngine,
		EntityRepository $carrierRepository,
		CarDeliveryConfig $carDeliveryConfig,
		ProductEntityFactory $productEntityFactory
	) {
		$this->formFactory          = $formFactory;
		$this->latteEngine          = $latteEngine;
		$this->carrierRepository    = $carrierRepository;
		$this->carDeliveryConfig    = $carDeliveryConfig;
		$this->productEntityFactory = $productEntityFactory;
	}

	/**
	 * Register component.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'woocommerce_product_data_tabs', [ $this, 'registerTab' ], 1, 1 );
		add_action( 'woocommerce_product_data_panels', [ $this, 'render' ] );
		add_action( 'woocommerce_process_product_meta', [ $this, 'saveData' ] );
	}

	/**
	 * Registers tab.
	 *
	 * @param array $tabs Tabs definition array.
	 *
	 * @return array
	 */
	public function registerTab( array $tabs ): array {
		$tabs[ self::NAME ] = [
			'label'  => __( 'Packeta', 'packeta' ),
			'target' => self::NAME,
			'class'  => [ 'hide_if_virtual', 'hide_if_downloadable' ],
		];

		return $tabs;
	}

	/**
	 * Creates form instance.
	 *
	 * @param Product\Entity $product Related product.
	 *
	 * @return Form
	 */
	private function createForm( Product\Entity $product ): Form {
		$form = $this->formFactory->create();
		$form->addCheckbox( Product\Entity::META_AGE_VERIFICATION_18_PLUS, __( 'Age verification 18+', 'packeta' ) );

		$carriersContainer = $form->addContainer( Product\Entity::META_DISALLOWED_SHIPPING_RATES );
		$carriersList      = $this->carrierRepository->getAllActiveCarriersList();
		foreach ( $carriersList as $carrier ) {
			if ( $this->carDeliveryConfig->isCarDeliveryCarrierDisabled( OptionPrefixer::removePrefix( $carrier['option_id'] ) ) ) {
				continue;
			}
			$carriersContainer->addCheckbox( $carrier['option_id'], $carrier['label'] );
		}

		$form->setDefaults(
			[
				Product\Entity::META_AGE_VERIFICATION_18_PLUS  => $product->isAgeVerification18PlusRequired(),
				Product\Entity::META_DISALLOWED_SHIPPING_RATES => $product->getDisallowedShippingRateChoices(),
			]
		);

		return $form;
	}

	/**
	 * Renders tab.
	 *
	 * @return void
	 */
	public function render(): void {
		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/product/data-tab-panel.latte',
			[
				'form'         => $this->createForm( $this->productEntityFactory->fromGlobals() ),
				'translations' => [
					'disallowedShippingRatesHeading' => __( 'Packeta shipping methods disabled for this product.', 'packeta' ),
				],
			]
		);
	}

	/**
	 * Saves product data.
	 *
	 * @param int|string $postId Post ID.
	 */
	public function saveData( $postId ): void {
		$product = $this->productEntityFactory->fromPostId( $postId );
		if ( false === $product->isPhysical() ) {
			return;
		}

		$form              = $this->createForm( $product );
		$form->onSuccess[] = function( Form $form, array $values ) use ( $product ) {
			$this->processFormData( $product->getId(), $values );
		};

		if ( $form->isSubmitted() ) {
			$form->fireEvents();
		}
	}

	/**
	 * Process form data.
	 *
	 * @param int   $productId Product ID.
	 * @param array $values    Form values.
	 */
	public function processFormData( int $productId, array $values ): void {
		foreach ( $values as $attr => $value ) {
			if ( is_bool( $value ) ) {
				$value = $value ? '1' : '0';
			}

			if ( Product\Entity::META_DISALLOWED_SHIPPING_RATES === $attr ) {
				$value = array_filter( $value );
			}

			update_post_meta( $productId, $attr, $value );
		}
	}
}
