<?php
/**
 * Class LabelPrint.
 *
 * @package Packetery\Order
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Api\Soap\Client;
use Packetery\Core\Api\Soap\Request;
use Packetery\Core\Api\Soap\Response;
use Packetery\Module\FormFactory;
use Packetery\Module\MessageManager;
use Packetery\Module\Options\Provider;
use PacketeryLatte\Engine;
use PacketeryNette\Forms\Form;
use PacketeryNette\Http;

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
	 * @var Http\Request
	 */
	private $httpRequest;

	/**
	 * SOAP API Client.
	 *
	 * @var Client SOAP API Client.
	 */
	private $soapApiClient;

	/**
	 * Message Manager.
	 *
	 * @var MessageManager
	 */
	private $messageManager;

	/**
	 * LabelPrint constructor.
	 *
	 * @param Engine         $latteEngine Latte Engine.
	 * @param Provider       $optionsProvider Options provider.
	 * @param FormFactory    $formFactory Form factory.
	 * @param Http\Request   $httpRequest Http Request.
	 * @param Client         $soapApiClient SOAP API Client.
	 * @param MessageManager $messageManager Message Manager.
	 */
	public function __construct(
		Engine $latteEngine,
		Provider $optionsProvider,
		FormFactory $formFactory,
		Http\Request $httpRequest,
		Client $soapApiClient,
		MessageManager $messageManager
	) {
		$this->latteEngine     = $latteEngine;
		$this->optionsProvider = $optionsProvider;
		$this->formFactory     = $formFactory;
		$this->httpRequest     = $httpRequest;
		$this->soapApiClient   = $soapApiClient;
		$this->messageManager  = $messageManager;
	}

	/**
	 * Returns form object and information about label formats.
	 *
	 * @return array
	 */
	private function getFormAndLabelsInfo(): array {
		$availableFormats  = $this->optionsProvider->getLabelFormats();
		$chosenLabelFormat = $this->optionsProvider->get_packeta_label_format();
		$maxOffset         = $availableFormats[ $chosenLabelFormat ]['maxOffset'];
		$form              = $this->createForm( $maxOffset );

		return [ $chosenLabelFormat, $maxOffset, $form ];
	}

	/**
	 * Prepares form and renders template.
	 */
	public function render(): void {
		[ $chosenLabelFormat, $maxOffset, $form ] = $this->getFormAndLabelsInfo();

		$errors = $this->httpRequest->getQuery( 'errors' );

		if ( $errors ) {
			$this->messageManager->flash_message( 'test', 'error' );
		}

		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/order/label-print.latte',
			[
				'form'   => $form,
				'errors' => $errors,
			]
		);
	}

	/**
	 * Outputs pdf.
	 */
	public function showLabelsPdf(): void {
		if ( ! $this->httpRequest->getQuery( 'orderIds' ) ) {
			return;
		}

		[ $chosenLabelFormat, $maxOffset, $form ] = $this->getFormAndLabelsInfo();
		$response                                 = null;
		if ( 0 === $maxOffset ) {
			$response = $this->prepareLabels( 0 );
		} elseif ( $form->isSubmitted() ) {
			$data     = $form->getValues( 'array' );
			$response = $this->prepareLabels( $data['offset'] );
		}

		if ( $response ) {
			if (
				$response->getFaultString() &&
				wp_safe_redirect( $this->httpRequest->getUrl()->withQueryParameter( 'errors', true ) )
			) {
				exit;
			}
			header( 'Content-Type: application/pdf' );
			header( 'Content-Transfer-Encoding: Binary' );
			header( 'Content-Length: ' . strlen( $response->getPdfContents() ) );
			$pdfFilename = 'packeta_labels_' . strtolower( str_replace( ' ', '_', $chosenLabelFormat ) ) . '.pdf';
			header( 'Content-Disposition: attachment; filename="' . $pdfFilename . '"' );
			// @codingStandardsIgnoreStart
			echo $response->getPdfContents();
			// @codingStandardsIgnoreEnd
			exit;
		}
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
		$form->setAction( $this->httpRequest->getUrl() );

		$availableOffsets = [];
		for ( $i = 0; $i <= $maxOffset; $i ++ ) {
			// translators: %s is offset.
			$availableOffsets[ $i ] = ( 0 === $i ? __( 'dontSkipAnyField', 'packetery' ) : sprintf( __( 'skip%sFields', 'packetery' ), $i ) );
		}
		$form->addSelect(
			'offset',
			__( 'labelsOffset', 'packetery' ),
			$availableOffsets
		)->checkDefaultValue( false );

		return $form;
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
	 *
	 * @return Response\PacketsLabelsPdf|null
	 */
	private function prepareLabels( int $offset ): ?Response\PacketsLabelsPdf {
		$orderIds           = explode( ',', $this->httpRequest->getQuery( 'orderIds' ) );
		$packetIds          = [];
		$labelRelatedOrders = [];
		if ( $orderIds ) {
			foreach ( $orderIds as $orderId ) {
				$order = Entity::fromPostId( $orderId );
				if ( null === $order || null === $order->getPacketId() ) {
					continue;
				}
				$labelRelatedOrders[] = $orderId;
				$packetIds[]          = $this->getPacketNumber( $order );
			}
		}
		if ( $packetIds ) {
			$request  = new Request\PacketsLabelsPdf( $packetIds, $this->optionsProvider->get_packeta_label_format(), $offset );
			$response = $this->soapApiClient->packetsLabelsPdf( $request );
			if ( ! $response->getFaultString() ) {
				foreach ( $labelRelatedOrders as $id ) {
					update_post_meta( $id, Entity::META_IS_LABEL_PRINTED, 1 );
				}
			}

			return $response;
		}

		return null;
	}

	/**
	 * Gets packet number without leading Z.
	 *
	 * @param Entity $order Order.
	 *
	 * @return string
	 */
	private function getPacketNumber( Entity $order ): string {
		return ltrim( $order->getPacketId(), 'Z' );
	}
}
