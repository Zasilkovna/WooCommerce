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
use Packetery\Core\CoreHelper;
use Packetery\Module\EntityFactory;
use Packetery\Module\FormFactory;
use Packetery\Module\FormRules;
use Packetery\Module\Message;
use Packetery\Module\MessageManager;
use Packetery\Latte\Engine;
use Packetery\Module\ModuleHelper;
use Packetery\Nette\Forms\Container;
use Packetery\Nette\Forms\Controls\BaseControl;
use Packetery\Nette\Forms\Controls\Checkbox;
use Packetery\Nette\Forms\Form;
use Packetery\Nette\Http\FileUpload;
use Packetery\Nette\Http\Request;
use Packetery\Module\CustomsDeclaration;

/**
 * Class CustomsDeclarationMetabox.
 */
class CustomsDeclarationMetabox {

	private const MAX_UPLOAD_FILE_MEGABYTES = 16;

	private const EAD_OWN     = 'own';
	private const EAD_CREATE  = 'create';
	private const EAD_CARRIER = 'carrier';

	public const FORM_ID             = 'packetery-customs-declaration-metabox-form';
	public const FORM_CONTAINER_NAME = 'packetery_customs_declaration';
	public const FORM_ACTIVATOR_NAME = 'fill_customs_declaration';

	/**
	 * Latte engine.
	 *
	 * @var Engine
	 */
	private $latteEngine;

	/**
	 * Form factory.
	 *
	 * @var FormFactory
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
	 * HTTP Request.
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
	 * Order detail comon logic.
	 *
	 * @var DetailCommonLogic
	 */
	private $detailCommonLogic;

	/**
	 * Constructor.
	 *
	 * @param Engine                           $latteEngine                     Latte engine.
	 * @param FormFactory                      $formFactory                     Form factory.
	 * @param CustomsDeclaration\Repository    $customsDeclarationRepository    Customs declaration repository.
	 * @param EntityFactory\CustomsDeclaration $customsDeclarationEntityFactory Customs declaration entity factory.
	 * @param Request                          $request                         Request.
	 * @param MessageManager                   $messageManager                  Message manager.
	 * @param DetailCommonLogic                $detailCommonLogic               Detail common logic.
	 */
	public function __construct(
		Engine $latteEngine,
		FormFactory $formFactory,
		CustomsDeclaration\Repository $customsDeclarationRepository,
		EntityFactory\CustomsDeclaration $customsDeclarationEntityFactory,
		Request $request,
		MessageManager $messageManager,
		DetailCommonLogic $detailCommonLogic
	) {
		$this->latteEngine                     = $latteEngine;
		$this->formFactory                     = $formFactory;
		$this->customsDeclarationRepository    = $customsDeclarationRepository;
		$this->customsDeclarationEntityFactory = $customsDeclarationEntityFactory;
		$this->request                         = $request;
		$this->messageManager                  = $messageManager;
		$this->detailCommonLogic               = $detailCommonLogic;
	}

	/**
	 * Registers related hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'add_meta_boxes', [ $this, 'addMetaBoxes' ] );
		add_action( 'admin_head', [ $this, 'renderTemplate' ] );
	}

	/**
	 * Adds meta boxes.
	 *
	 * @return void
	 */
	public function addMetaBoxes(): void {
		$order = $this->detailCommonLogic->getOrder();

		if (
			null === $order ||
			false === $order->getCarrier()->requiresCustomsDeclarations()
		) {
			return;
		}

		add_meta_box(
			'packetery_customs_declaration_metabox',
			__( 'Customs declaration', 'packeta' ),
			[ $this, 'render' ],
			ModuleHelper::isHposEnabled() ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order',
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
		$this->addCustomsDeclarationItem( $formTemplate->addCheckbox( self::FORM_ACTIVATOR_NAME ), $items, '0' );

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
	 * @param Order $order Order ID.
	 * @return void
	 */
	public function saveFields( Order $order ): void {
		if (
			( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
			null === $this->request->getPost( self::FORM_CONTAINER_NAME )
		) {
			return;
		}

		$form = $this->createForm(
			$this->request->getPost(),
			$this->customsDeclarationRepository->getByOrderNumber( $order->getNumber() )
		);

		if ( $form->isSubmitted() ) {
			$form->fireEvents();
		}
	}

	/**
	 * Renders meta box.
	 *
	 * @return void
	 */
	public function render(): void {
		$order = $this->detailCommonLogic->getOrder();
		if ( null === $order ) {
			return;
		}

		$customsDeclaration = $this->customsDeclarationRepository->getByOrderNumber( $order->getNumber() );

		$formData = [];
		if ( null !== $customsDeclaration ) {
			$formData                                       = [
				self::FORM_CONTAINER_NAME => $this->customsDeclarationRepository->declarationToDbArray(
					$customsDeclaration,
					[ 'invoice_file', 'ead_file', 'order_id' ]
				),
			];
			$formData[ self::FORM_CONTAINER_NAME ]['items'] = [];

			$customsDeclarationItems = $this->customsDeclarationRepository->getItemsByCustomsDeclarationId( $customsDeclaration->getId() );
			foreach ( $customsDeclarationItems as $customsDeclarationItem ) {
				$formData[ self::FORM_CONTAINER_NAME ]['items'][ $customsDeclarationItem->getId() ] = $this->customsDeclarationRepository->declarationItemToDbArray( $customsDeclarationItem );
			}
		}

		$form = $this->createForm( $formData, $customsDeclaration );
		$form->setDefaults( $formData );

		$hasInvoiceFile = null !== $customsDeclaration && $customsDeclaration->hasInvoiceFileContent();
		$hasEadFile     = null !== $customsDeclaration && $customsDeclaration->hasEadFileContent();

		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/order/customs-declaration-metabox.latte',
			[
				'form'           => $form,
				'hasInvoiceFile' => $hasInvoiceFile,
				'hasEadFile'     => $hasEadFile,
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
	 * @param array                          $structureData Data specifying how form will be constructed.
	 *                                                      When user browser sends added customs declaration items, the form factory has to reflect that.
	 * @param Entity\CustomsDeclaration|null $customsDeclaration Related customs declaration.
	 *
	 * @return Form
	 */
	private function createForm( array $structureData, ?Entity\CustomsDeclaration $customsDeclaration ): Form {
		$form = $this->formFactory->create();

		$activator = $form->addCheckbox( self::FORM_ACTIVATOR_NAME, __( 'View/hide customs declaration form', 'packeta' ) );
		$activator
			->addCondition( Form::FILLED )
				->toggle( 'customs-declaration-container' );

		$prefixContainer = $form->addContainer( self::FORM_CONTAINER_NAME );

		$ead = $prefixContainer->addSelect(
			'ead',
			__( 'EAD', 'packeta' ),
			[
				self::EAD_OWN     => __( 'Self-declaration (I have a EAD)', 'packeta' ),
				self::EAD_CREATE  => __( 'Issuing a EAD via Packeta', 'packeta' ),
				self::EAD_CARRIER => __( 'Postal clearance (no EAD and no fees)', 'packeta' ),
			]
		);
		$ead
			->addConditionOn( $activator, Form::FILLED )
				->setRequired();

		$prefixContainer->addText( 'delivery_cost', __( 'Delivery cost', 'packeta' ) )
			->addConditionOn( $activator, Form::FILLED )
				->setRequired()
				->addRule( Form::FLOAT )
				->addRule( ...FormRules::getGreaterThanParameters( 0 ) );

		$prefixContainer->addText( 'invoice_number', __( 'Invoice number', 'packeta' ) )
			->addConditionOn( $activator, Form::FILLED )
				->setRequired();

		$prefixContainer->addText( 'invoice_issue_date', __( 'Invoice issue date', 'packeta' ) )
			->addConditionOn( $activator, Form::FILLED )
				->setRequired()
				->addRule( ...FormRules::getDateParameters() );

		$invoiceFile = $prefixContainer->addUpload( 'invoice_file', __( 'Invoice PDF file', 'packeta' ) )
			->setRequired( false );

		if ( null === $customsDeclaration || false === $customsDeclaration->hasInvoiceFileContent() ) {
			$invoiceFile
				->addConditionOn( $activator, Form::FILLED )
					->addConditionOn( $ead, Form::EQUAL, self::EAD_OWN )
						->setRequired()
					->endCondition()
					->addConditionOn( $ead, Form::EQUAL, self::EAD_CREATE )
						->setRequired();
		}

		$prefixContainer->addText( 'mrn', __( 'MRN', 'packeta' ) )
			->setRequired( false )
			->addConditionOn( $activator, Form::FILLED )
				->addRule( Form::MAX_LENGTH, null, 32 )
				->addConditionOn( $ead, Form::EQUAL, self::EAD_OWN )
					->toggle( 'customs-declaration-own-field-mrn' )
					->setRequired();

		$eadFile = $prefixContainer->addUpload( 'ead_file', __( 'EAD PDF file', 'packeta' ) )
			->setRequired( false )
			->addConditionOn( $ead, Form::EQUAL, self::EAD_OWN )
				->toggle( 'customs-declaration-own-field-ead_file' );

		if ( null === $customsDeclaration || false === $customsDeclaration->hasEadFileContent() ) {
			$eadFile
				->addConditionOn( $activator, Form::FILLED )
					->addConditionOn( $ead, Form::EQUAL, self::EAD_OWN )
					->setRequired();
		}

		$form->addSubmit( 'save' );

		$items = $prefixContainer->addContainer( 'items' );

		if ( empty( $structureData[ self::FORM_CONTAINER_NAME ]['items'] ) ) {
			$this->addCustomsDeclarationItem( $activator, $items, 'new_0' );
		} else {
			foreach ( $structureData[ self::FORM_CONTAINER_NAME ]['items'] as $itemId => $itemDefaults ) {
				$this->addCustomsDeclarationItem( $activator, $items, (string) $itemId );
			}
		}

		$form->onSuccess[] = [ $this, 'onFormSuccess' ];
		$form->onError[]   = [ $this, 'onFormError' ];

		return $form;
	}

	/**
	 * On form error.
	 *
	 * @param Form $form Form.
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
		$order = $this->detailCommonLogic->getOrder();
		if ( null === $order ) {
			return;
		}

		$fieldsToOmit = [];
		/** Form container. @var Container $customsDeclarationContainer */
		$customsDeclarationContainer = $form[ self::FORM_CONTAINER_NAME ];
		$prefixedValues              = $form->getValues( 'array' );
		$containerValues             = $prefixedValues[ self::FORM_CONTAINER_NAME ];
		$items                       = $containerValues['items'];
		unset( $containerValues['items'] );

		if ( false === $prefixedValues[ self::FORM_ACTIVATOR_NAME ] ) {
			return;
		}

		$this->processUploadedFile(
			'invoice_file',
			'invoice_file_id',
			$containerValues,
			$customsDeclarationContainer,
			$fieldsToOmit
		);

		$this->processUploadedFile(
			'ead_file',
			'ead_file_id',
			$containerValues,
			$customsDeclarationContainer,
			$fieldsToOmit
		);

		if ( '' === $containerValues['mrn'] ) {
			$containerValues['mrn'] = null;
		}

		$containerValues['id'] = null;
		$oldCustomsDeclaration = $this->customsDeclarationRepository->getByOrderNumber( $order->getNumber() );
		if ( null !== $oldCustomsDeclaration ) {
			$containerValues['id'] = $oldCustomsDeclaration->getId();
		}

		$customsDeclaration = $this->customsDeclarationEntityFactory->fromStandardizedStructure( $containerValues, $order->getNumber() );
		$customsDeclaration->setInvoiceFile( $containerValues['invoice_file'], (bool) $containerValues['invoice_file'] );
		$customsDeclaration->setEadFile( $containerValues['ead_file'], (bool) $containerValues['ead_file'] );
		$this->customsDeclarationRepository->save( $customsDeclaration, $fieldsToOmit );

		$customsDeclarationItems = $this->customsDeclarationRepository->getItemsByCustomsDeclarationId( $customsDeclaration->getId() );
		$customsDeclaration->setItems( $customsDeclarationItems );
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

			$item['id']                     = $itemId;
			$item['customs_declaration_id'] = $customsDeclaration->getId();
			$this->customsDeclarationRepository->saveItem(
				$this->customsDeclarationEntityFactory->createItemFromStandardizedStructure( $item )
			);
		}
	}

	/**
	 * Adds customs declaration item.
	 *
	 * @param Checkbox  $activator Activating checkbox.
	 * @param Container $container Container.
	 * @param string    $index     Item index.
	 *
	 * @return void
	 */
	public function addCustomsDeclarationItem( Checkbox $activator, Container $container, string $index ): void {
		$item = $container->addContainer( $index );
		$item->addText( 'customs_code', __( 'Customs code', 'packeta' ) )
			->addConditionOn( $activator, Form::FILLED )
				->setRequired()
				->addRule( Form::MAX_LENGTH, null, 8 );

		$item->addText( 'value', __( 'Value', 'packeta' ) )
			->addConditionOn( $activator, Form::FILLED )
				->setRequired()
				->addRule( Form::FLOAT )
				->addRule( ...FormRules::getGreaterThanParameters( 0 ) );

		$item->addText( 'product_name_en', __( 'Product name (EN)', 'packeta' ) )
			->addConditionOn( $activator, Form::FILLED )
				->setRequired();
		$item->addText( 'product_name', __( 'Product name', 'packeta' ) );

		$item->addText( 'units_count', __( 'Units count', 'packeta' ) )
			->addConditionOn( $activator, Form::FILLED )
				->setRequired()
				->addRule( Form::INTEGER )
				->addRule( ...FormRules::getGreaterThanParameters( 0 ) );

		$item->addText( 'country_of_origin', __( 'Country of origin code', 'packeta' ) )
			->addConditionOn( $activator, Form::FILLED )
				->setRequired()
				->addRule( Form::LENGTH, null, 2 );

		$item->addText( 'weight', __( 'Weight (kg)', 'packeta' ) )
			->addConditionOn( $activator, Form::FILLED )
				->setRequired()
				->addRule( Form::FLOAT )
				->addRule( ...FormRules::getGreaterThanParameters( 0 ) )
				->addFilter(
					static function ( float $value ): float {
						return CoreHelper::simplifyWeight( $value );
					}
				)
				->addRule( ...FormRules::getGreaterThanParameters( 0 ) );

		$item->addCheckbox( 'is_food_or_book', __( 'Food or book?', 'packeta' ) );
		$item->addCheckbox( 'is_voc', __( 'Is VOC?', 'packeta' ) );
	}

	/**
	 * Handle file upload.
	 *
	 * @param string    $key              File key.
	 * @param string    $relatedFileIdKey Related file id key.
	 * @param array     $containerValues  Container values.
	 * @param Container $formContainer    Form container.
	 * @param array     $fieldsToOmit     Fields to omit.
	 *
	 * @return void
	 */
	private function processUploadedFile( string $key, string $relatedFileIdKey, array &$containerValues, Container $formContainer, array &$fieldsToOmit ): void {
		$fileUpload    = $containerValues[ $key ];
		$uploadControl = $formContainer[ $key ];

		if ( $fileUpload->hasFile() && 0 >= $fileUpload->getSize() ) {
			$formContainer[ $key ]->addError( __( 'Uploaded file is empty.', 'packeta' ) );
			$fileUpload = new FileUpload( null );
		}

		if ( $fileUpload->hasFile() && self::MAX_UPLOAD_FILE_MEGABYTES * 1024 * 1024 === $fileUpload->getSize() ) {
			// translators: %d is numeric value.
			$formContainer[ $key ]->addError( sprintf( __( 'Uploaded file is too big for storage. Max size is %d MB.', 'packeta' ), self::MAX_UPLOAD_FILE_MEGABYTES ) );
			$fileUpload = new FileUpload( null );
		}

		if ( $fileUpload->hasFile() && $fileUpload->isOk() ) {
			$containerValues[ $key ]              = static function () use ( $fileUpload ): string {
				return $fileUpload->getContents();
			};
			$containerValues[ $relatedFileIdKey ] = null;
		}

		if ( $fileUpload->hasFile() && false === $fileUpload->isOk() ) {
			$containerValues[ $key ]              = null;
			$containerValues[ $relatedFileIdKey ] = null;
			$uploadControl->addError( __( 'File failed to upload.', 'packeta' ) );
		}

		if ( $containerValues[ $key ] instanceof FileUpload ) {
			$containerValues[ $key ]              = null;
			$containerValues[ $relatedFileIdKey ] = null;
			$fieldsToOmit[]                       = $key;
			$fieldsToOmit[]                       = $relatedFileIdKey;
		}
	}
}
