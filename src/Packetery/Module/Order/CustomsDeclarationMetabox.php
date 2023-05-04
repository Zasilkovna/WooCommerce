<?php
/**
 * Class CustomsDeclarationMetabox.
 *
 * @package Packetery
 */

declare(strict_types=1);

namespace Packetery\Module\Order;

use Packetery\Core\Helper;
use Packetery\Module\FormFactory;
use Packetery\Module\FormRulesParts;
use PacketeryLatte\Engine;
use PacketeryNette\Forms\Container;
use PacketeryNette\Forms\Form;

/**
 * Class CustomsDeclarationMetabox.
 */
class CustomsDeclarationMetabox {

	private const EAD_OWN     = 'own';
	private const EAD_CREATE  = 'create';
	private const EAD_CARRIER = 'carrier';

	private const FORM_NAME = 'customs-declaration-metabox-form';

	/**
	 * Order repository.
	 *
	 * @var Repository
	 */
	private $orderRepository;

	/**
	 * Latte engine.
	 *
	 * @var \PacketeryLatte\Engine
	 */
	private $latteEngine;

	/**
	 * Form factory.
	 *
	 * @var \Packetery\Module\FormFactory
	 */
	private $formFactory;

	/**
	 * Constructor.
	 *
	 * @param \Packetery\Module\Order\Repository $orderRepository Order repository.
	 * @param \PacketeryLatte\Engine             $latteEngine Latte engine.
	 * @param \Packetery\Module\FormFactory      $formFactory Form factory.
	 */
	public function __construct(
		Repository $orderRepository,
		Engine $latteEngine,
		FormFactory $formFactory
	) {
		$this->orderRepository = $orderRepository;
		$this->latteEngine     = $latteEngine;
		$this->formFactory     = $formFactory;
	}

	/**
	 * Registers related hooks.
	 */
	public function register(): void {
		add_action( 'add_meta_boxes', [ $this, 'addMetaBoxes' ] );
		add_action( 'admin_head', [ $this, 'renderTemplate' ] );
	}

	/**
	 * Adds meta boxes.
	 */
	public function addMetaBoxes(): void {
		global $post;

		$order = $this->orderRepository->getById( (int) $post->ID );

		if (
			null === $order ||
			null === $order->getCarrier() ||
			false === $order->getCarrier()->requiresCustomsDeclarations()
		) {
			return;
		}

		add_meta_box(
			'packetery_customs_declaration_metabox',
			__( 'Customs declaration', 'packeta' ),
			[ $this, 'render' ],
			'shop_order',
			'advanced',
			'high'
		);
	}

	/**
	 * Renders template.
	 *
	 * @return void
	 */
	public function renderTemplate(): void {
		$formTemplate = $this->formFactory->create( sprintf( '%s_template', self::FORM_NAME ) );
		$items        = $formTemplate->addContainer( 'items' );
		$this->addCustomsDeclarationItem( $items, '0' );

		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/order/customs-declaration-form-template.latte',
			[
				'formTemplate' => $formTemplate,
				'translations' => [
					'delete' => __( 'Delete', 'packeta' ),
				],
			]
		);
	}

	/**
	 * Renders meta box.
	 *
	 * @return void
	 */
	public function render(): void {
		global $post;

		$order = $this->orderRepository->getById( (int) $post->ID );
		if ( null === $order ) {
			return;
		}

		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/order/customs-declaration-metabox.latte',
			[
				'form'         => $this->createForm(),
				'translations' => [
					'addCustomsDeclarationItem' => __( 'Add item', 'packeta' ),
					'delete'                    => __( 'Delete', 'packeta' ),
					'itemsLabel'                => __( 'Items', 'packeta' ),
				],
			]
		);
	}

	/**
	 * Creates form.
	 *
	 * @return Form
	 */
	private function createForm(): Form {
		$form = $this->formFactory->create( self::FORM_NAME );

		$ead = $form->addSelect(
			'ead',
			__( 'EAD', 'packeta' ),
			[
				self::EAD_OWN     => __( 'Self-declaration (I have a EAD)', 'packeta' ),
				self::EAD_CREATE  => __( 'Issuing a EAD via Packeta', 'packeta' ),
				self::EAD_CARRIER => __( 'Postal clearance (no EAD and no fees)', 'packeta' ),
			]
		)
			->setRequired();

		$form->addText( 'delivery_cost', __( 'Delivery cost', 'packeta' ) )
			->setRequired()
			->addRule( Form::FLOAT )
			->addRule( ...FormRulesParts::greaterThan( 0 ) );

		$form->addText( 'invoice_number', __( 'Invoice number', 'packeta' ) )
			->setRequired();

		$form->addText( 'invoice_issue_date', __( 'Invoice issue date', 'packeta' ) )
			->setRequired();

		$form->addUpload( 'invoice', __( 'Invoice PDF file', 'packeta' ) )
			->setRequired( false )
			->addConditionOn( $ead, Form::EQUAL, self::EAD_OWN )
				->setRequired()
			->endCondition()
			->addConditionOn( $ead, Form::EQUAL, self::EAD_CREATE )
				->setRequired();

		$form->addText( 'mrn', __( 'MRN', 'packeta' ) )
			->addConditionOn( $ead, Form::EQUAL, self::EAD_OWN )
				->toggle( 'customs-declaration-own-field-mrn' )
				->setRequired();

		$form->addUpload( 'ead_file', __( 'EAD PDF file', 'packeta' ) )
			->addConditionOn( $ead, Form::EQUAL, self::EAD_OWN )
				->toggle( 'customs-declaration-own-field-ead_file' )
				->setRequired();

		$items = $form->addContainer( 'items' );
		$this->addCustomsDeclarationItem( $items, '0' );

		return $form;
	}

	/**
	 * Adds customs declaration item.
	 *
	 * @param \PacketeryNette\Forms\Container $container Container.
	 * @param string                          $index Item index.
	 * @return void
	 */
	public function addCustomsDeclarationItem( Container $container, string $index ): void {
		$item = $container->addContainer( $index );
		$item->addText( 'code', __( 'Code', 'packeta' ) )
			->setRequired();

		$item->addText( 'value', __( 'Value', 'packeta' ) )
			->setRequired()
			->addRule( Form::FLOAT )
			->addRule( ...FormRulesParts::greaterThan( 0 ) );

		$item->addText( 'product_name_en', __( 'Product name (EN)', 'packeta' ) )
			->setRequired();
		$item->addText( 'product_name', __( 'Product name', 'packeta' ) );

		$item->addText( 'amount', __( 'Amount', 'packeta' ) )
			->setRequired()
			->addRule( Form::INTEGER )
			->addRule( ...FormRulesParts::greaterThan( 0 ) );

		$item->addText( 'country_code', __( 'Country of origin code', 'packeta' ) )
			->setRequired()
			->addRule( Form::LENGTH, null, 2 );

		$item->addText( 'weight', __( 'Weight (kg)', 'packeta' ) )
			->setRequired()
			->addRule( Form::FLOAT )
			->addRule( ...FormRulesParts::greaterThan( 0 ) )
			->addFilter(
				static function ( float $value ): float {
					return Helper::simplifyWeight( $value );
				}
			)
			->addRule( ...FormRulesParts::greaterThan( 0 ) );

		$item->addCheckbox( 'food_or_book', __( 'Food or book?', 'packeta' ) );
		$item->addCheckbox( 'voc', __( 'Is VOC?', 'packeta' ) );
	}
}
