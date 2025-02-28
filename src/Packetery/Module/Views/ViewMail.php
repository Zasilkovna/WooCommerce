<?php

namespace Packetery\Module\Views;

use Packetery\Latte\Engine;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\ModuleHelper;
use Packetery\Module\Order\DetailCommonLogic;
use Packetery\Module\Order\Repository;
use WC_Email;
use WC_Order;

class ViewMail {

	/**
	 * @var Repository
	 */
	private $orderRepository;

	/**
	 * @var Engine
	 */
	private $latteEngine;

	/**
	 * @var DetailCommonLogic
	 */
	private $detailCommonLogic;

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	public function __construct(
		Repository $orderRepository,
		Engine $latteEngine,
		DetailCommonLogic $detailCommonLogic,
		WpAdapter $wpAdapter
	) {

		$this->orderRepository   = $orderRepository;
		$this->latteEngine       = $latteEngine;
		$this->detailCommonLogic = $detailCommonLogic;
		$this->wpAdapter         = $wpAdapter;
	}

	/**
	 *  Renders email footer.
	 *
	 * @param mixed $email Email data.
	 */
	public function renderEmailFooter( $email ): void {
		$wcOrder = null;
		if ( ( $email instanceof WC_Email ) && ( $email->object instanceof WC_Order ) ) {
			$wcOrder = $email->object;
		}

		if ( $email instanceof WC_Order ) {
			$wcOrder = $email;
		}

		if ( $wcOrder === null ) {
			return;
		}

		$order = $this->orderRepository->getByWcOrderWithValidCarrier( $wcOrder );
		if ( $order === null ) {
			return;
		}

		if ( $this->detailCommonLogic->shouldHidePacketaInfo( $order ) ) {
			return;
		}

		$templateParams = [
			'displayPickupPointInfo' => $this->detailCommonLogic->shouldDisplayPickupPointInfo(),
			'order'                  => $order,
			'translations'           => [
				'packeta'              => $this->wpAdapter->__( 'Packeta', 'packeta' ),
				'pickupPointDetail'    => $this->wpAdapter->__( 'Pickup Point Detail', 'packeta' ),
				'pickupPointName'      => $this->wpAdapter->__( 'Pickup Point Name', 'packeta' ),
				'link'                 => $this->wpAdapter->__( 'Link', 'packeta' ),
				'pickupPointAddress'   => $this->wpAdapter->__( 'Pickup Point Address', 'packeta' ),
				'packetTrackingOnline' => $this->wpAdapter->__( 'Packet tracking online', 'packeta' ),
			],
		];
		$emailHtml      = $this->latteEngine->renderToString(
			PACKETERY_PLUGIN_DIR . '/template/email/order.latte',
			$templateParams
		);
		/**
		 * This filter allows you to change the HTML of e-mail footer.
		 *
		 * @since 1.5.3
		 */
		ModuleHelper::renderString( (string) $this->wpAdapter->applyFilters( 'packeta_email_footer', $emailHtml, $templateParams ) );
	}
}
