<?php

declare(strict_types=1);

namespace Packetery\Module\Order;

use Packetery\Core\Helper;
use Packetery\Module\FormValidators;
use PacketeryNette\Forms\Form;

class CustomsDeclarationMetabox
{
    /** @var Repository */
    private $orderRepository;

    /** @var \PacketeryLatte\Engine */
    private $latteEngine;

    /** @var \Packetery\Module\FormFactory */
    private $formFactory;

    /**
     * @param \Packetery\Module\Order\Repository $orderRepository
     * @param \PacketeryLatte\Engine $latteEngine
     * @param \Packetery\Module\FormFactory $formFactory
     */
    public function __construct(Repository $orderRepository, \PacketeryLatte\Engine $latteEngine, \Packetery\Module\FormFactory $formFactory)
    {
        $this->orderRepository = $orderRepository;
        $this->latteEngine = $latteEngine;
        $this->formFactory = $formFactory;
    }

    /**
     * Registers related hooks.
     */
    public function register(): void {
        add_action('add_meta_boxes', [$this, 'addMetaBoxes']);
    }

    /**
     * Adds meta boxes.
     */
    public function addMetaBoxes(): void {
        global $post;

        $order = $this->orderRepository->getById( (int) $post->ID );
        if ( null === $order ) {
            return;
        }

        // todo
//        if ( null === $order->getCarrier() || false === $order->getCarrier()->requiresCustomsDeclarations() ) {
//            return;
//        }

        add_meta_box(
            'packetery_customs_declaration_metabox',
            __( 'Customs declaration', 'packeta' ),
            [ $this, 'render', ],
            'shop_order',
            'advanced',
            'high'
        );
        add_action( 'admin_head', [ $this, 'renderTemplate' ] );
    }

    public function renderTemplate(): void
    {
        $formTemplate = $this->formFactory->create( 'customs-declaration-metabox-form_template' );
        $items = $formTemplate->addContainer('items');
        $this->addCustomsDeclarationItem($items, '0');

        $this->latteEngine->render(
            PACKETERY_PLUGIN_DIR . '/template/order/customs-declaration-form-template.latte',
            [
                'formTemplate' => $formTemplate,
                'translations' => [
                    'addCustomsDeclarationItem' => __('Add item', 'packeta'),
                    'delete' => __('Delete', 'packeta'),
                    'itemsLabel' => __('Items', 'packeta'),
                ]
            ]
        );
    }

    /**
     * Renders meta box.
     *
     * @return void
     */
    public function render(): void
    {
        global $post;

        $wcOrder = $this->orderRepository->getWcOrderById( (int) $post->ID );
        if ( null === $wcOrder ) {
            return;
        }

        $order = $this->orderRepository->getByWcOrder( $wcOrder );
        if ( null === $order ) {
            return;
        }

        $form = $this->formFactory->create( 'customs-declaration-metabox-form' );

        $form->addSelect(
            'ead',
            __( 'EAD', 'packeta' ),
            [
                //Vlastní celní prohlášení (mám VDD) (own)
                //Vystavení VDD přes Zásilkovnu (create)
                //Poštovní clení (bez VDD a bez poplatků) (carrier)
                'own' => __( 'Own', 'packeta' ),
                'create' => __( 'Create', 'packeta' ),
                'carrier' => __( 'Carrier', 'packeta' ),
            ]
        );

        $form->addText('delivery_cost', __( 'Delivery cost', 'packeta' ))
            ->setRequired()
            ->addRule(Form::FLOAT)
            ->addRule(Form::MIN, null, 0); // todo no zero


        $form->addText('invoice_number', __( 'Invoice number', 'packeta' ))
            ->setRequired();

        $form->addText('invoice_issue_date', __( 'Invoice issue date', 'packeta' ))
            ->setRequired();

        $form->addUpload('invoice', __( 'Invoice', 'packeta' ))
            ->setRequired(false)
            ->addConditionOn($form['ead'], Form::EQUAL, 'own')
            ->setRequired()
            ->elseCondition()
            ->addConditionOn($form['ead'], Form::EQUAL, 'create')
            ->setRequired();

        $form->addText('mrn', __( 'MRN', 'packeta' ))
            ->addConditionOn($form['ead'], Form::EQUAL, 'own')
            ->toggle('ead_own_fields')
            ->setRequired();

        $form->addUpload('vdd', __( 'VDD', 'packeta' ))
            ->addConditionOn($form['ead'], Form::EQUAL, 'own')
            ->toggle('ead_own_fields')
            ->setRequired();

        $items = $form->addContainer('items');
        $this->addCustomsDeclarationItem($items, '0');

        $this->latteEngine->render(
            PACKETERY_PLUGIN_DIR . '/template/order/customs-declaration-metabox.latte',
            [
                'form' => $form,
                'translations' => [
                    'addCustomsDeclarationItem' => __('Add item', 'packeta'),
                    'delete' => __('Delete', 'packeta'),
                    'itemsLabel' => __('Items', 'packeta'),
                ],
            ]
        );
    }

    public function addCustomsDeclarationItem(\PacketeryNette\Forms\Container $container, string $index ): void
    {
        $item = $container->addContainer( $index );
        $item->addText('code', __('code', 'packeta'))
            ->setRequired();

        $item->addText('value', __('value', 'packeta'))
            ->setRequired()
            ->addRule(Form::FLOAT)
            // translators: %d is numeric threshold.
            ->addRule( [ FormValidators::class, 'greaterThan' ], __( 'Enter number greater than %d', 'packeta' ), 0.0 );

        $item->addText('productNameEn', __('Product name (EN)', 'packeta') )
            ->setRequired();
        $item->addText('productName', __('Product name', 'packeta'));

        $item->addText('count', __('count', 'packeta'))
            ->setRequired()
            ->addRule(Form::INTEGER)
            // translators: %d is numeric threshold.
            ->addRule( [ FormValidators::class, 'greaterThan' ], __( 'Enter number greater than %d', 'packeta' ), 0.0 );

        $item->addText('countryCode', __('Country Code', 'packeta'))
            ->addRule(Form::MIN_LENGTH, null, 2);

        $item->addText('weight', __('Weight (kg)', 'packeta'))
            ->setRequired()
            // translators: %d is numeric threshold.
            ->addRule( [ FormValidators::class, 'greaterThan' ], __( 'Enter number greater than %d', 'packeta' ), 0.0 );
        $item['weight']->addFilter(
            static function ( float $value ): float {
                return Helper::simplifyWeight( $value );
            }
        );
        // translators: %d is numeric threshold.
        $item['weight']->addRule( [ FormValidators::class, 'greaterThan' ], __( 'Enter number greater than %d', 'packeta' ), 0.0 );

        $item->addCheckbox('food_or_book', __('Food or book?', 'packeta'));
        $item->addCheckbox('voc', __('Is VOC?', 'packeta'));
    }
}
