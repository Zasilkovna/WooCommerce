<?php
/**
 * Class LabelPrintModal.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Module;
use Packetery\Latte\Engine;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Nette\Forms\Form;

/**
 * Class LabelPrintModal.
 *
 * @package Packetery
 */
class LabelPrintModal {

	public const MODAL_ID_PACKET       = 'wc-packetery-order-detail-packet-label-print-modal';
	public const MODAL_ID_PACKET_CLAIM = 'wc-packetery-order-detail-packet-claim-label-print-modal';

	/**
	 * Latte engine.
	 *
	 * @var Engine
	 */
	private $latteEngine;

	/**
	 * Label print.
	 *
	 * @var LabelPrint
	 */
	private $labelPrint;

	/**
	 * Options provider.
	 *
	 * @var OptionsProvider
	 */
	private $optionsProvider;

	/**
	 * Context resolver.
	 *
	 * @var Module\ContextResolver
	 */
	private $contextResolver;

	/**
	 * Order detail common logic.
	 *
	 * @var DetailCommonLogic
	 */
	private $detailCommonLogic;

	/**
	 * Constructor.
	 *
	 * @param Engine                 $latteEngine       Latte engine.
	 * @param LabelPrint             $labelPrint        Label print.
	 * @param OptionsProvider        $optionsProvider   Options provider.
	 * @param Module\ContextResolver $contextResolver   Context resolver.
	 * @param DetailCommonLogic      $detailCommonLogic Detail common logic.
	 */
	public function __construct(
		Engine $latteEngine,
		LabelPrint $labelPrint,
		OptionsProvider $optionsProvider,
		Module\ContextResolver $contextResolver,
		DetailCommonLogic $detailCommonLogic
	) {
		$this->latteEngine       = $latteEngine;
		$this->labelPrint        = $labelPrint;
		$this->optionsProvider   = $optionsProvider;
		$this->contextResolver   = $contextResolver;
		$this->detailCommonLogic = $detailCommonLogic;
	}

	/**
	 * Registers order modal.
	 */
	public function register(): void {
		add_action( 'admin_head', [ $this, 'renderPacketModal' ] );
		add_action( 'admin_head', [ $this, 'renderPacketClaimModal' ] );
	}

	/**
	 * Renders packet modal.
	 */
	public function renderPacketModal(): void {
		if ( false === $this->contextResolver->isOrderDetailPage() ) {
			return;
		}

		$order = $this->detailCommonLogic->getOrder();
		if ( null === $order || null === $order->getPacketId() ) {
			return;
		}

		$this->renderModal(
			self::MODAL_ID_PACKET,
			$this->labelPrint->getLabelFormatByOrder( $order ),
			$order->getPacketId()
		);
	}

	/**
	 * Renders packet claim modal.
	 */
	public function renderPacketClaimModal(): void {
		if ( false === $this->contextResolver->isOrderDetailPage() ) {
			return;
		}

		$order = $this->detailCommonLogic->getOrder();
		if ( null === $order || null === $order->getPacketClaimId() ) {
			return;
		}

		$this->renderModal(
			self::MODAL_ID_PACKET_CLAIM,
			$this->labelPrint->getLabelFormatByOrder( $order ),
			$order->getPacketClaimId()
		);
	}

	/**
	 * Renders packet modal.
	 *
	 * @param string $id       Modal ID.
	 * @param string $format   Label format.
	 * @param string $packetId Packet ID.
	 *
	 * @return void
	 */
	private function renderModal( string $id, string $format, string $packetId ): void {
		$form = $this->createForm( sprintf( '%s_form', $id ), $format, $packetId );
		if ( $form['submit']->isSubmittedBy() ) {
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

	/**
	 * Creates order modal form.
	 *
	 * @param string $name     Form name.
	 * @param string $format   Format.
	 * @param string $packetId Packet ID.
	 *
	 * @return Form
	 */
	public function createForm( string $name, string $format, string $packetId ): Form {
		$form = $this->labelPrint->createForm( $this->optionsProvider->getLabelMaxOffset( $format ), $name );
		$form->addHidden( 'packet_id' )->setDefaultValue( $packetId );
		$form->addSubmit( 'submit', __( 'Print', 'packeta' ) );
		$form->addSubmit( 'cancel', __( 'Cancel', 'packeta' ) );

		$form->onSuccess[] = [ $this, 'onFormSuccess' ];

		return $form;
	}

	/**
	 * On form success.
	 *
	 * @param Form $form Form.
	 *
	 * @return void
	 */
	public function onFormSuccess( Form $form ): void {
		$order = $this->detailCommonLogic->getOrder();
		if ( null === $order ) {
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
