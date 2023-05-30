<?php
/**
 * Class CustomsDeclarationMetabox.
 *
 * @package Packetery
 */

declare(strict_types=1);

namespace Packetery\Module\Order;

use Packetery\Core\Entity;
use Packetery\Core\Entity\Order;
use Packetery\Core\Helper;
use Packetery\Module\EntityFactory;
use Packetery\Module\FormFactory;
use Packetery\Module\FormRulesParts;
use Packetery\Module\Message;
use Packetery\Module\MessageManager;
use PacketeryLatte\Engine;
use PacketeryNette\Forms\Container;
use PacketeryNette\Forms\Controls\BaseControl;
use PacketeryNette\Forms\Form;
use PacketeryNette\Http\FileUpload;
use PacketeryNette\Http\Request;
use Packetery\Module\CustomsDeclaration;

/**
 * Class CustomsDeclarationMetabox.
 */
class CustomsDeclarationMetabox {

	private const EAD_OWN     = 'own';
	private const EAD_CREATE  = 'create';
	private const EAD_CARRIER = 'carrier';

	public const FORM_ID             = 'packetery-customs-declaration-metabox-form';
	public const FORM_CONTAINER_NAME = 'packetery_customs_declaration';

	/**
	 * Relevant order.
	 *
	 * @var Order|null
	 */
	private $order = null;

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
	 * Customs declaration repository.
	 *
	 * @var CustomsDeclaration\Repository
	 */
	private $customsDeclarationRepository;

	/**
	 * Customs declaration entity factory.
	 *
	 * @var EntityFactory\CustomsDeclaration
	 */
	private $customsDeclarationEntityFactory;

	/**
	 * Request.
	 *
	 * @var Request
	 */
	private $request;

	/**
	 * Message manager.
	 *
	 * @var MessageManager
	 */
	private $messageManager;

	/**
	 * Constructor.
	 *
	 * @param Repository                       $orderRepository                 Order repository.
	 * @param \PacketeryLatte\Engine           $latteEngine                     Latte engine.
	 * @param \Packetery\Module\FormFactory    $formFactory                     Form factory.
	 * @param CustomsDeclaration\Repository    $customsDeclarationRepository    Customs declaration repository.
	 * @param EntityFactory\CustomsDeclaration $customsDeclarationEntityFactory Customs declaration entity factory.
	 * @param Request                          $request                         Request.
	 * @param MessageManager                   $messageManager                  Message manager.
	 */
	public function __construct(
		Repository $orderRepository,
		Engine $latteEngine,
		FormFactory $formFactory,
		CustomsDeclaration\Repository $customsDeclarationRepository,
		EntityFactory\CustomsDeclaration $customsDeclarationEntityFactory,
		Request $request,
		MessageManager $messageManager
	) {
		$this->orderRepository                 = $orderRepository;
		$this->latteEngine                     = $latteEngine;
		$this->formFactory                     = $formFactory;
		$this->customsDeclarationRepository    = $customsDeclarationRepository;
		$this->customsDeclarationEntityFactory = $customsDeclarationEntityFactory;
		$this->request                         = $request;
		$this->messageManager                  = $messageManager;
	}

	/**
	 * Gets order.
	 *
	 * @return Order|null
	 */
	private function getOrder(): ?Order {
		global $post;

		if ( null === $this->order ) {
			$this->order = $this->orderRepository->getById( (int) $post->ID );
		}

		return $this->order;
	}

	/**
	 * Registers related hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'add_meta_boxes', [ $this, 'addMetaBoxes' ] );
		add_action( 'admin_head', [ $this, 'renderTemplate' ] );
		add_action( 'save_post', [ $this, 'saveFields' ] );
	}

	/**
	 * Adds meta boxes.
	 *
	 * @return void
	 */
	public function addMetaBoxes(): void {
		$order = $this->getOrder();

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
		$formTemplate    = $this->formFactory->create();
		$prefixContainer = $formTemplate->addContainer( self::FORM_CONTAINER_NAME );
		$items           = $prefixContainer->addContainer( 'items' );
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
	 * Saves submitted form fields data.
	 *
	 * @param int|mixed $orderId Order ID.
	 * @return int|mixed
	 */
	public function saveFields( $orderId ) {
		$order = $this->getOrder();
		if (
			null === $order ||
			( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
			null === $this->request->getPost( self::FORM_CONTAINER_NAME )
		) {
			return $orderId;
		}

		$form = $this->createForm(
			$this->request->getPost(),
			$this->customsDeclarationRepository->getByOrder( $order )
		);

		if ( $form->isSubmitted() ) {
			$form->fireEvents();
		}

		return $orderId;
	}

	/**
	 * Renders meta box.
	 *
	 * @return void
	 */
	public function render(): void {
		$order = $this->getOrder();
		if ( null === $order ) {
			return;
		}

		$customsDeclaration = $this->customsDeclarationRepository->getByOrder( $order );

		$formData = [];
		if ( null !== $customsDeclaration ) {
			$formData                                       = [
				self::FORM_CONTAINER_NAME => $this->customsDeclarationRepository->declarationToDbArray(
					$customsDeclaration,
					[ 'invoice_file', 'ead_file', 'order_id' ]
				),
			];
			$formData[ self::FORM_CONTAINER_NAME ]['items'] = [];

			$customsDeclarationItems = $this->customsDeclarationRepository->getItemsByCustomsDeclaration( $customsDeclaration );
			foreach ( $customsDeclarationItems as $customsDeclarationItem ) {
				$formData[ self::FORM_CONTAINER_NAME ]['items'][ $customsDeclarationItem->getId() ] = $this->customsDeclarationRepository->declarationItemToDbArray( $customsDeclarationItem );
			}
		}

		$form = $this->createForm( $formData, $customsDeclaration );
		$form->setDefaults( $formData );

		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/order/customs-declaration-metabox.latte',
			[
				'form'           => $form,
				'hasInvoiceFile' => $this->customsDeclarationRepository->hasInvoiceFile( $customsDeclaration ),
				'hasEadFile'     => $this->customsDeclarationRepository->hasEadFile( $customsDeclaration ),
				'translations'   => [
					'addCustomsDeclarationItem' => __( 'Add item', 'packeta' ),
					'delete'                    => __( 'Delete', 'packeta' ),
					'itemsLabel'                => __( 'Items', 'packeta' ),
					'fileUploaded'              => __( 'File uploaded.', 'packeta' ),
				],
			]
		);
	}

	/**
	 * Creates form.
	 *
	 * @param array                          $data Structure specifying data.
	 * @param Entity\CustomsDeclaration|null $customsDeclaration Related customs declaration.
	 * @return Form
	 */
	private function createForm( array $data, ?Entity\CustomsDeclaration $customsDeclaration ): Form {
		$form            = $this->formFactory->create();
		$prefixContainer = $form->addContainer( self::FORM_CONTAINER_NAME );

		$ead = $prefixContainer->addSelect(
			'ead',
			__( 'EAD', 'packeta' ),
			[
				self::EAD_OWN     => __( 'Self-declaration (I have a EAD)', 'packeta' ),
				self::EAD_CREATE  => __( 'Issuing a EAD via Packeta', 'packeta' ),
				self::EAD_CARRIER => __( 'Postal clearance (no EAD and no fees)', 'packeta' ),
			]
		)
			->setRequired();

		$prefixContainer->addText( 'delivery_cost', __( 'Delivery cost', 'packeta' ) )
			->setRequired()
			->addRule( Form::FLOAT )
			->addRule( ...FormRulesParts::greaterThan( 0 ) );

		$prefixContainer->addText( 'invoice_number', __( 'Invoice number', 'packeta' ) )
			->setRequired();

		$prefixContainer->addText( 'invoice_issue_date', __( 'Invoice issue date', 'packeta' ) )
			->setRequired();

		$invoiceFile = $prefixContainer->addUpload( 'invoice_file', __( 'Invoice PDF file', 'packeta' ) )
			->setRequired( false );

		if ( false === $this->customsDeclarationRepository->hasInvoiceFile( $customsDeclaration ) ) {
			$invoiceFile
				->addConditionOn( $ead, Form::EQUAL, self::EAD_OWN )
					->setRequired()
				->endCondition()
				->addConditionOn( $ead, Form::EQUAL, self::EAD_CREATE )
					->setRequired();
		}

		$prefixContainer->addText( 'mrn', __( 'MRN', 'packeta' ) )
			->setRequired( false )
			->addRule( Form::MAX_LENGTH, null, 32 )
			->addConditionOn( $ead, Form::EQUAL, self::EAD_OWN )
				->toggle( 'customs-declaration-own-field-mrn' )
				->setRequired();

		$eadFile = $prefixContainer->addUpload( 'ead_file', __( 'EAD PDF file', 'packeta' ) )
			->setRequired( false )
			->addConditionOn( $ead, Form::EQUAL, self::EAD_OWN )
				->toggle( 'customs-declaration-own-field-ead_file' );

		if ( false === $this->customsDeclarationRepository->hasEadFile( $customsDeclaration ) ) {
			$eadFile
				->addConditionOn( $ead, Form::EQUAL, self::EAD_OWN )
				->setRequired();
		}

		$form->addSubmit( 'save' );

		$items = $prefixContainer->addContainer( 'items' );

		if ( empty( $data[ self::FORM_CONTAINER_NAME ]['items'] ) ) {
			$this->addCustomsDeclarationItem( $items, 'new_0' );
		} else {
			foreach ( $data[ self::FORM_CONTAINER_NAME ]['items'] as $itemId => $itemDefaults ) {
				$this->addCustomsDeclarationItem( $items, (string) $itemId );
			}
		}

		$form->onSuccess[] = [ $this, 'onFormSuccess' ];
		$form->onError[]   = [ $this, 'onFormError' ];

		return $form;
	}

	/**
	 * On form error.
	 *
	 * @param \PacketeryNette\Forms\Form $form Form.
	 * @return void
	 */
	public function onFormError( Form $form ): void {
		/** Form input control. @var BaseControl[] $controls */
		$controls = $form->getComponents( true, BaseControl::class );
		foreach ( $controls as $control ) {
			foreach ( $control->getErrors() as $error ) {
				$this->messageManager->flashMessageObject(
					Message::create()
						->setText( sprintf( '%s: %s', $control->getCaption(), $error ) )
						->setType( MessageManager::TYPE_ERROR )
				);
			}
		}
	}

	/**
	 * On form success callback.
	 *
	 * @param Form $form Form.
	 * @return void
	 */
	public function onFormSuccess( Form $form ): void {
		$order = $this->getOrder();
		if ( null === $order ) {
			return;
		}

		$fieldsToOmit                = [];
		$customsDeclarationContainer = $form[ self::FORM_CONTAINER_NAME ];
		$prefixedValues              = $form->getValues( 'array' );
		$values                      = $prefixedValues[ self::FORM_CONTAINER_NAME ];
		$items                       = $values['items'];
		unset( $values['items'] );

		/** Invoice file. @var \PacketeryNette\Http\FileUpload $invoiceFile */
		$invoiceFile = $values['invoice_file'];
		/** EAD file. @var \PacketeryNette\Http\FileUpload $eadFile */
		$eadFile = $values['ead_file'];

		if ( $invoiceFile->hasFile() && $invoiceFile->getSize() >= 16 * 1024 * 1024 ) {
			// translators: %d is numeric value.
			$customsDeclarationContainer['invoice_file']->addError( sprintf( __( 'Uploaded file is too big for storage. Max size is %d MB.', 'packeta' ), 16 ) );
			$invoiceFile = new FileUpload( null );
		}

		if ( $eadFile->hasFile() && $eadFile->getSize() >= 16 * 1024 * 1024 ) {
			// translators: %d is numeric value.
			$customsDeclarationContainer['ead_file']->addError( sprintf( __( 'Uploaded file is too big for storage. Max size is %d MB.', 'packeta' ), 16 ) );
			$eadFile = new FileUpload( null );
		}

		if ( $invoiceFile->hasFile() && $invoiceFile->isOk() ) {
			$values['invoice_file']    = static function () use ( $invoiceFile ): string {
				return $invoiceFile->getContents();
			};
			$values['invoice_file_id'] = null;
		}

		if ( $invoiceFile->hasFile() && false === $invoiceFile->isOk() ) {
			$values['invoice_file']    = null;
			$values['invoice_file_id'] = null;
			$customsDeclarationContainer['invoice_file']->addError( __( 'Uploaded file is not OK.', 'packeta' ) );
		}

		if ( $values['invoice_file'] instanceof FileUpload ) {
			$values['invoice_file']    = null;
			$values['invoice_file_id'] = null;
			$fieldsToOmit[]            = 'invoice_file';
			$fieldsToOmit[]            = 'invoice_file_id';
		}

		if ( $eadFile->hasFile() && $eadFile->isOk() ) {
			$values['ead_file']    = static function () use ( $eadFile ): string {
				return $eadFile->getContents();
			};
			$values['ead_file_id'] = null;
		}

		if ( $eadFile->hasFile() && false === $eadFile->isOk() ) {
			$values['ead_file']    = null;
			$values['ead_file_id'] = null;
			$customsDeclarationContainer['ead_file']->addError( __( 'Uploaded file is not OK.', 'packeta' ) );
		}

		if ( $values['ead_file'] instanceof FileUpload ) {
			$values['ead_file']    = null;
			$values['ead_file_id'] = null;
			$fieldsToOmit[]        = 'ead_file';
			$fieldsToOmit[]        = 'ead_file_id';
		}

		if ( '' === $values['mrn'] ) {
			$values['mrn'] = null;
		}

		$values['id']          = null;
		$oldCustomsDeclaration = $this->customsDeclarationRepository->getByOrder( $order );
		if ( null !== $oldCustomsDeclaration ) {
			$values['id'] = $oldCustomsDeclaration->getId();
		}

		$customsDeclaration = $this->customsDeclarationEntityFactory->fromStandardizedStructure( $values, $order );
		$customsDeclaration->setInvoiceFile( $values['invoice_file'] );
		$customsDeclaration->setEadFile( $values['ead_file'] );
		$this->customsDeclarationRepository->save( $customsDeclaration, $fieldsToOmit );

		$customsDeclarationItems = $this->customsDeclarationRepository->getItemsByCustomsDeclaration( $customsDeclaration );
		foreach ( $customsDeclarationItems as $customsDeclarationItem ) {
			$itemId = $customsDeclarationItem->getId();
			if ( ! isset( $items[ $itemId ] ) ) {
				$this->customsDeclarationRepository->deleteItem( (int) $itemId );
			}
		}

		foreach ( $items as $itemId => $item ) {
			if ( 0 === strpos( (string) $itemId, 'new_' ) ) {
				$itemId = null;
			} else {
				$itemId = (string) $itemId;
			}

			$item['id'] = $itemId;
			$this->customsDeclarationRepository->saveItem(
				$this->customsDeclarationEntityFactory->createItemFromStandardizedStructure(
					$item,
					$customsDeclaration
				)
			);
		}
	}

	/**
	 * Adds customs declaration item.
	 *
	 * @param Container $container Container.
	 * @param string    $index Item index.
	 * @return void
	 */
	public function addCustomsDeclarationItem( Container $container, string $index ): void {
		$item = $container->addContainer( $index );
		$item->addText( 'customs_code', __( 'Customs code', 'packeta' ) )
			->setRequired()
			->addRule( Form::MAX_LENGTH, null, 8 );

		$item->addText( 'value', __( 'Value', 'packeta' ) )
			->setRequired()
			->addRule( Form::FLOAT )
			->addRule( ...FormRulesParts::greaterThan( 0 ) );

		$item->addText( 'product_name_en', __( 'Product name (EN)', 'packeta' ) )
			->setRequired();
		$item->addText( 'product_name', __( 'Product name', 'packeta' ) );

		$item->addText( 'units_count', __( 'Units count', 'packeta' ) )
			->setRequired()
			->addRule( Form::INTEGER )
			->addRule( ...FormRulesParts::greaterThan( 0 ) );

		$item->addText( 'country_of_origin', __( 'Country of origin code', 'packeta' ) )
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

		$item->addCheckbox( 'is_food_or_book', __( 'Food or book?', 'packeta' ) );
		$item->addCheckbox( 'is_voc', __( 'Is VOC?', 'packeta' ) );
	}
}
