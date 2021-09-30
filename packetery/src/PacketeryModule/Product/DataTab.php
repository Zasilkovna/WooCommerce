<?php
/**
 * Packetery product tab.
 *
 * @package PacketeryModule\Product
 */

declare( strict_types=1 );


namespace PacketeryModule\Product;

use PacketeryModule\Product;
use PacketeryLatte\Engine;
use PacketeryNette\Forms\Form;
use PacketeryNette\Forms\FormFactory;

/**
 * Class Tab
 *
 * @package PacketeryModule\Product
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
	 * Tab constructor.
	 *
	 * @param FormFactory $formFactory Factory engine.
	 * @param Engine      $latteEngine Latte engine.
	 */
	public function __construct( FormFactory $formFactory, Engine $latteEngine ) {
		$this->formFactory = $formFactory;
		$this->latteEngine = $latteEngine;
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
			'label'  => __( 'Packeta', 'packetery' ),
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
		$form = $this->formFactory->createForm();
		$form->addCheckbox( Product\Entity::META_AGE_VERIFICATION_18_PLUS, __( 'ageVerification18PlusLabel', 'packetery' ) );

		$form->setDefaults(
			[ Product\Entity::META_AGE_VERIFICATION_18_PLUS => $product->isAgeVerification18PlusRequired() ]
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
			[ 'form' => $this->createForm( Product\Entity::fromGlobals() ) ]
		);
	}

	/**
	 * Saves product data.
	 *
	 * @param int|string $postId Post ID.
	 */
	public function saveData( $postId ): void {
		$product = Product\Entity::fromPostId( $postId );
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

			update_post_meta( $productId, $attr, $value );
		}
	}
}
