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
	 * Product to be processed.
	 *
	 * @var Product\Entity
	 */
	private $product;

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
		$this->product = Product\Entity::fromGlobals();
		if ( false === $this->product->isRelevant() ) {
			return $tabs;
		}

		$tabs[ self::NAME ] = [
			'label'  => __( 'Packeta', 'packetery' ),
			'target' => self::NAME,
			'class' => [ 'hide_if_virtual', 'hide_if_downloadable' ],
		];

		return $tabs;
	}

	/**
	 * Creates form instance.
	 *
	 * @return Form
	 */
	private function createForm(): Form {
		$this->product = Product\Entity::fromGlobals();

		$form = $this->formFactory->createForm();
		$form->addCheckbox( Product\Entity::META_AGE_VERIFICATION_18_PLUS, __( 'ageVerification18PlusLabel', 'packetery' ) );

		$form->setDefaults(
			[ Product\Entity::META_AGE_VERIFICATION_18_PLUS => $this->product->isAgeVerification18PlusEnabled() ]
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
			[ 'form' => $this->createForm() ]
		);
	}

	/**
	 * Saves product data.
	 *
	 * @param int|string $postId Post ID.
	 */
	public function saveData( $postId ): void {
		$this->product = Product\Entity::fromPostId( $postId );
		if ( false === $this->product->isRelevant() ) {
			return;
		}

		$form              = $this->createForm();
		$form->onSuccess[] = [ $this, 'processFormData' ];

		if ( $form->isSubmitted() ) {
			$form->fireEvents();
		}
	}

	/**
	 * @param Form $form Form.
	 */
	public function processFormData( Form $form ): void {
		$values = $form->getValues( 'array' );
		update_post_meta( $this->product->getId(), Product\Entity::META_AGE_VERIFICATION_18_PLUS, ( $values[ Product\Entity::META_AGE_VERIFICATION_18_PLUS ] ? '1' : '0' ) );
	}
}
