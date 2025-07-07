<?php

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Latte\Engine;
use Packetery\Module;
use Packetery\Module\Labels\LabelPrintParametersService;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Nette\Forms\Controls\SubmitButton;
use Packetery\Nette\Forms\Form;

class LabelPrintModal {

	public const MODAL_ID_PACKET       = 'wc-packetery-order-detail-packet-label-print-modal';
	public const MODAL_ID_PACKET_CLAIM = 'wc-packetery-order-detail-packet-claim-label-print-modal';

	/**
	 * @var Engine
	 */
	private $latteEngine;

	/**
	 * @var LabelPrintParametersService
	 */
	private $labelPrintParametersService;

	/**
	 * @var OptionsProvider
	 */
	private $optionsProvider;

	/**
	 * @var Module\ContextResolver
	 */
	private $contextResolver;

	/**
	 * @var DetailCommonLogic
	 */
	private $detailCommonLogic;

	public function __construct(
		Engine $latteEngine,
		LabelPrintParametersService $labelPrintParametersService,
		OptionsProvider $optionsProvider,
		Module\ContextResolver $contextResolver,
		DetailCommonLogic $detailCommonLogic
	) {
		$this->latteEngine                 = $latteEngine;
		$this->labelPrintParametersService = $labelPrintParametersService;
		$this->optionsProvider             = $optionsProvider;
		$this->contextResolver             = $contextResolver;
		$this->detailCommonLogic           = $detailCommonLogic;
	}

	public function register(): void {
		add_action( 'admin_head', [ $this, 'renderPacketModal' ] );
		add_action( 'admin_head', [ $this, 'renderPacketClaimModal' ] );
	}

	public function renderPacketModal(): void {
		if ( $this->contextResolver->isOrderDetailPage() === false ) {
			return;
		}

		$order = $this->detailCommonLogic->getOrder();
		if ( $order === null || $order->getPacketId() === null ) {
			return;
		}

		$this->renderModal(
			self::MODAL_ID_PACKET,
			$this->labelPrintParametersService->getLabelFormatByOrder( $order ),
			$order->getPacketId()
		);
	}

	public function renderPacketClaimModal(): void {
		if ( $this->contextResolver->isOrderDetailPage() === false ) {
			return;
		}

		$order = $this->detailCommonLogic->getOrder();
		if ( $order === null || $order->getPacketClaimId() === null ) {
			return;
		}

		$this->renderModal(
			self::MODAL_ID_PACKET_CLAIM,
			$this->labelPrintParametersService->getLabelFormatByOrder( $order ),
			$order->getPacketClaimId()
		);
	}

	private function renderModal( string $id, string $format, string $packetId ): void {
		$form         = $this->createForm( sprintf( '%s_form', $id ), $format, $packetId );
		$submitButton = $form['submit'];
		if ( $submitButton instanceof SubmitButton &&
			$submitButton->isSubmittedBy()
		) {
			$form->fireEvents();
		}

		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/order/label-print-modal.latte',
			[
				'id'           => $id,
				'form'         => $form,
				'translations' => [
					// translators: %s is packet ID.
					'header'          => sprintf( __( 'Label print of packet %s', 'packeta' ), $packetId ),

					'closeModalPanel' => __( 'Close modal panel', 'packeta' ),
				],
			]
		);
	}

	public function createForm( string $name, string $format, string $packetId ): Form {
		$form = $this->labelPrintParametersService->createForm( $this->optionsProvider->getLabelMaxOffset( $format ), $name );
		$form->addHidden( 'packet_id' )->setDefaultValue( $packetId );
		$form->addSubmit( 'submit', __( 'Print', 'packeta' ) );
		$form->addSubmit( 'cancel', __( 'Cancel', 'packeta' ) );

		$form->onSuccess[] = [ $this, 'onFormSuccess' ];

		return $form;
	}

	public function onFormSuccess( Form $form ): void {
		$order = $this->detailCommonLogic->getOrder();
		if ( $order === null ) {
			return;
		}

		$values    = $form->getValues();
		$printLink = add_query_arg(
			[
				'page'                                    => LabelPrint::MENU_SLUG,
				LabelPrint::LABEL_TYPE_PARAM              => ( $order->isExternalCarrier() ? LabelPrint::ACTION_CARRIER_LABELS : LabelPrint::ACTION_PACKETA_LABELS ),
				'id'                                      => $order->getNumber(),
				PacketActionsCommonLogic::PARAM_PACKET_ID => $values['packet_id'],
				'offset'                                  => $values['offset'],
				PacketActionsCommonLogic::PARAM_REDIRECT_TO => PacketActionsCommonLogic::REDIRECT_TO_ORDER_DETAIL,
			],
			admin_url( 'admin.php' )
		);

		if ( wp_safe_redirect( $printLink ) ) {
			exit;
		}
	}
}
