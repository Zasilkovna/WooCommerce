<?php

declare( strict_types=1 );

namespace Packetery\Module\Labels;

use Packetery\Core\Entity\Order;
use Packetery\Module\FormFactory;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Order\LabelPrint;
use Packetery\Nette\Forms\Form;
use Packetery\Nette\Http;

class LabelPrintParametersService {

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * @var OptionsProvider
	 */
	private $optionsProvider;

	/**
	 * @var FormFactory
	 */
	private $formFactory;

	/**
	 * @var Http\Request
	 */
	private $httpRequest;

	public function __construct(
		WpAdapter $wpAdapter,
		OptionsProvider $optionsProvider,
		FormFactory $formFactory,
		Http\Request $httpRequest
	) {
		$this->wpAdapter       = $wpAdapter;
		$this->optionsProvider = $optionsProvider;
		$this->formFactory     = $formFactory;
		$this->httpRequest     = $httpRequest;
	}

	/**
	 * Creates offset setting form.
	 *
	 * @param int         $maxOffset Maximal offset.
	 * @param string|null $name      Form name.
	 *
	 * @return Form
	 */
	public function createForm( int $maxOffset, ?string $name = null ): Form {
		$form = $this->formFactory->create( $name );

		$availableOffsets = [];
		for ( $i = 0; $i <= $maxOffset; $i++ ) {
			$availableOffsets[ $i ] = ( $i === 0 ?
				$this->wpAdapter->__( "don't skip any field on a print sheet", 'packeta' ) :
				// translators: %s is offset.
				sprintf( $this->wpAdapter->__( 'skip %s fields on first sheet', 'packeta' ), $i )
			);
		}
		$form->addSelect(
			'offset',
			$this->wpAdapter->__( 'Skip fields', 'packeta' ),
			$availableOffsets
		)->checkDefaultValue( false );

		return $form;
	}

	public function getOffset(): ?int {
		$maxOffset   = $this->optionsProvider->getLabelMaxOffset( $this->getLabelFormat() );
		$form        = $this->createForm( $maxOffset );
		$offsetParam = $this->httpRequest->getQuery( 'offset' );
		if ( $maxOffset === 0 ) {
			return 0;
		}
		if ( $offsetParam !== null ) {
			return (int) $offsetParam;
		}
		if ( $form->isSubmitted() ) {
			$data = $form->getValues( 'array' );

			return (int) $data['offset'];
		}

		return null;
	}

	public function removeExternalCarriers( LabelPrintPacketData $labelPrintPacketData, bool $isCarrierLabels, bool $fallbackToPacketaLabel ): LabelPrintPacketData {
		if ( $isCarrierLabels === true || $fallbackToPacketaLabel === true ) {
			return $labelPrintPacketData;
		}

		$result = new LabelPrintPacketData();
		foreach ( $labelPrintPacketData->getItems() as $item ) {
			$order = $item->getOrder();
			if ( ! $order->isExternalCarrier() ) {
				$result->addItem( $order, $item->getPacketId() );
			}
		}

		return $result;
	}

	/**
	 * Gets label format for current job.
	 *
	 * @return string
	 */
	public function getLabelFormat(): string {
		$packetaLabelFormat = $this->optionsProvider->get_packeta_label_format();
		$carrierLabelFormat = $this->optionsProvider->get_carrier_label_format();

		return ( $this->httpRequest->getQuery( LabelPrint::LABEL_TYPE_PARAM ) === LabelPrint::ACTION_CARRIER_LABELS ? $carrierLabelFormat : $packetaLabelFormat );
	}

	public function getLabelFormatByOrder( Order $order ): string {
		if ( $order->isExternalCarrier() ) {
			return $this->optionsProvider->get_carrier_label_format();
		}

		return $this->optionsProvider->get_packeta_label_format();
	}
}
