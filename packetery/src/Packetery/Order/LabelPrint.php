<?php
/**
 * Class LabelPrint.
 *
 * @package Packetery\Order
 */

namespace Packetery\Order;

use Packetery\FormFactory;
use Packetery\Options\Provider;
use PacketeryLatte\Engine;
use PacketeryNette\Forms\Form;
use PacketeryNette\Http\Request;

/**
 * Class LabelPrint.
 *
 * @package Packetery\Order
 */
class LabelPrint {
	/**
	 * PacketeryLatte Engine.
	 *
	 * @var Engine PacketeryLatte engine.
	 */
	private $latteEngine;

	/**
	 * Options Provider
	 *
	 * @var Provider
	 */
	private $optionsProvider;

	/**
	 * Form factory.
	 *
	 * @var FormFactory Form factory.
	 */
	private $formFactory;

	/**
	 * Http Request.
	 *
	 * @var Request
	 */
	private $httpRequest;

	/**
	 * LabelPrint constructor.
	 *
	 * @param Engine      $latteEngine Latte Engine.
	 * @param Provider    $optionsProvider Options provider.
	 * @param FormFactory $formFactory Form factory.
	 * @param Request     $httpRequest Http Request.
	 */
	public function __construct(
		Engine $latteEngine,
		Provider $optionsProvider,
		FormFactory $formFactory,
		Request $httpRequest
	) {
		$this->latteEngine     = $latteEngine;
		$this->optionsProvider = $optionsProvider;
		$this->formFactory     = $formFactory;
		$this->httpRequest     = $httpRequest;
	}

	/**
	 * Prepares form and renders template.
	 */
	public function render(): void {
		$availableFormats  = $this->optionsProvider->getLabelFormats();
		$chosenLabelFormat = $this->optionsProvider->get_packeta_label_format();
		$maxOffset         = $availableFormats[ $chosenLabelFormat ]['maxOffset'];
		if ( 0 === $maxOffset ) {
			$this->prepareLabels( 0 );
		}

		$form = $this->createForm( $maxOffset );
		if ( $form->isSubmitted() ) {
			$form->fireEvents();
		}
		$this->latteEngine->render( PACKETERY_PLUGIN_DIR . '/template/order/label-print.latte', [ 'form' => $form ] );
	}

	/**
	 * Creates offset setting form.
	 *
	 * @param int $maxOffset Maximal offset.
	 *
	 * @return Form
	 */
	public function createForm( int $maxOffset ): Form {
		$form = $this->formFactory->create();

		$availableOffsets = [];
		for ( $i = 0; $i <= $maxOffset; $i ++ ) {
			// translators: %s is offset.
			$availableOffsets[ $i ] = ( $i === 0 ? __( 'dontSkipAnyField', 'packetery' ) : sprintf( __( 'skip%sFields', 'packetery' ), $i ) );
		}
		$form->addSelect(
			'offset',
			__( 'labelsOffset', 'packetery' ),
			$availableOffsets
		)->checkDefaultValue( false );

		$form->onSuccess[] = [ $this, 'setOffset' ];

		return $form;
	}

	/**
	 * Processes form when sent.
	 *
	 * @param Form $form Form.
	 *
	 * @return void
	 */
	public function setOffset( Form $form ): void {
		$data = $form->getValues( 'array' );
		$this->prepareLabels( $data['offset'] );
	}

	/**
	 * Registers submenu item.
	 */
	public function register(): void {
		add_submenu_page(
			'packeta-options',
			__( 'printLabels', 'packetery' ),
			__( 'printLabels', 'packetery' ),
			'manage_options',
			'label-print',
			array(
				$this,
				'render',
			),
			20
		);
	}

	/**
	 * Hides submenu item.
	 */
	public function hideFromMenus(): void {
		global $submenu;
		if ( isset( $submenu['packeta-options'] ) ) {
			foreach ( $submenu['packeta-options'] as $key => $menu ) {
				if ( 'label-print' === $menu[2] ) {
					unset( $submenu['packeta-options'][ $key ] );
				}
			}
		}
	}

	/**
	 * Prepares labels.
	 *
	 * @param int $offset Offset value.
	 */
	private function prepareLabels( int $offset ): void {
		// TODO: prepare labels here.
	}
}
