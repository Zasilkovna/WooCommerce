<?php
/**
 * Packetery product tab.
 *
 * @package Packetery\Product
 */

declare( strict_types=1 );


namespace Packetery\Product;

use Packetery\Product;
use PacketeryLatte\Engine;
use PacketeryNette\Forms\Form;
use PacketeryNette\Forms\FormFactory;

/**
 * Class Tab
 *
 * @package Packetery\Product
 */
class Tab {

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
		$product = Product\Entity::fromGlobals();
		if ( false === $product->isRelevant() ) {
			return $tabs;
		}

		$tabs[ self::NAME ] = [
			'label'  => __( 'Packeta', 'packetery' ),
			'target' => self::NAME,
		];

		return $tabs;
	}

	/**
	 * Creates form instance.
	 *
	 * @return Form
	 */
	private function createForm(): Form {
		$product = Product\Entity::fromGlobals();

		$form = $this->formFactory->createForm();
		$form->addCheckbox( Product\Entity::META_AGE_VERIFICATION_18_PLUS, __( 'Age verification 18+', 'packetery' ) );

		$form->setDefaults(
			[
				Product\Entity::META_AGE_VERIFICATION_18_PLUS => $product->isAgeVerification18PlusEnabled(),
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
			PACKETERY_PLUGIN_DIR . '/template/product/tab.latte',
			[
				'form' => $this->createForm(),
			]
		);
	}

	/**
	 * Saves product data.
	 *
	 * @param int|string $postId Post ID.
	 */
	public function saveData( $postId ): void {
		$product = Product\Entity::fromPostId( $postId );
		if ( false === $product->isRelevant() ) {
			return;
		}

		$form              = $this->createForm();
		$form->onSuccess[] = function ( Form $form ) use ( $postId ) {
			$values = $form->getValues( 'array' );
			update_post_meta( $postId, Product\Entity::META_AGE_VERIFICATION_18_PLUS, ( $values[ Product\Entity::META_AGE_VERIFICATION_18_PLUS ] ? '1' : '0' ) );
		};

		if ( $form->isSubmitted() ) {
			$form->fireEvents();
		}
	}
}
