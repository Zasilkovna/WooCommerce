<?php

declare( strict_types=1 );

namespace Packetery\Module\Dashboard;

use Packetery\Module\Carrier;
use Packetery\Module\Carrier\CarrierUpdater;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Order\PacketSynchronizer;
use Packetery\Module\Product;
use WP_Query;

class DashboardItemBuilder {

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * @var DashboardHelper
	 */
	private $dashboardHelper;

	/**
	 * @var OptionsProvider
	 */
	private $optionsProvider;

	/**
	 * @var Carrier\EntityRepository
	 */
	private $carrierEntityRepository;

	/**
	 * @var CarrierUpdater
	 */
	private $carrierUpdater;

	/**
	 * @var PacketSynchronizer
	 */
	private $packetSynchronizer;

	public function __construct(
		WpAdapter $wpAdapter,
		DashboardHelper $dashboardHelper,
		OptionsProvider $optionsProvider,
		Carrier\EntityRepository $carrierEntityRepository,
		CarrierUpdater $carrierUpdater,
		PacketSynchronizer $packetSynchronizer
	) {
		$this->wpAdapter               = $wpAdapter;
		$this->dashboardHelper         = $dashboardHelper;
		$this->optionsProvider         = $optionsProvider;
		$this->carrierEntityRepository = $carrierEntityRepository;
		$this->carrierUpdater          = $carrierUpdater;
		$this->packetSynchronizer      = $packetSynchronizer;
	}

	/**
	 * @return DashboardItem[]
	 */
	public function buildItems(): array {
		return [
			new DashboardItem(
				$this->wpAdapter->__( 'Basic settings of the Packeta plugin', 'packeta' ),
				$this->wpAdapter->adminUrl( 'admin.php?page=' . Options\Page::SLUG . '&wizard-enabled=true&wizard-general-settings-tour-enabled=true' ),
				$this->wpAdapter->__( 'Start with this to start using the plugin.', 'packeta' ),
				1,
				$this->optionsProvider->get_api_password() !== null && $this->optionsProvider->get_sender() !== null
			),
			new DashboardItem(
				$this->wpAdapter->__( 'Carrier type setting', 'packeta' ),
				$this->wpAdapter->adminUrl( 'admin.php?page=' . Options\Page::SLUG . '&tab=' . Options\Page::TAB_ADVANCED . '&wizard-enabled=true&wizard-advanced-tour-enabled=true' ),
				$this->wpAdapter->__( 'Here you can choose whether to use one shipping method for all carriers or separate methods for each carrier (recommended).', 'packeta' ),
				2,
				$this->optionsProvider->isWcCarrierConfigEnabledNullable() !== null
			),
			new DashboardItem(
				$this->wpAdapter->__( 'Product settings', 'packeta' ),
				$this->getNewestProductUrl(),
				$this->getProductSettingsDescription(),
				3,
				$this->hasProductsWithPacketaSettings()
			),
			new DashboardItem(
				$this->wpAdapter->__( 'Carriers update', 'packeta' ),
				$this->getCarrierUpdateUrl(),
				$this->wpAdapter->__( 'Load the current list of external carriers so that you can use them.', 'packeta' ),
				4,
				$this->carrierUpdater->getLastUpdate() !== null
			),
			new DashboardItem(
				$this->wpAdapter->__( 'Carrier settings', 'packeta' ),
				$this->wpAdapter->adminUrl( 'admin.php?page=' . Carrier\OptionsPage::SLUG ),
				$this->wpAdapter->__( 'Set prices, weight limits and other settings for the carriers you want to use.', 'packeta' ),
				5,
				count( $this->carrierEntityRepository->getAllActiveCarriersList() ) > 0
			),
			new DashboardItem(
				$this->wpAdapter->__( 'Shipping zone settings', 'packeta' ),
				$this->wpAdapter->adminUrl( 'admin.php?page=wc-settings&tab=shipping' ),
				$this->wpAdapter->__( 'Assign shipping methods to WooCommerce zones so that they are offered at checkout.', 'packeta' ),
				6,
				$this->dashboardHelper->isPacketaShippingMethodActive()
			),
			new DashboardItem(
				$this->wpAdapter->__( 'Set up shipment status tracking', 'packeta' ),
				$this->wpAdapter->adminUrl( 'admin.php?page=' . Options\Page::SLUG . '&tab=' . Options\Page::TAB_PACKET_STATUS_SYNC . '&wizard-enabled=true&wizard-packet-status-tracking-tour-enabled=true' ),
				$this->wpAdapter->__( 'Always keep an eye on the current status of your shipment. This status will be displayed in the order overview.', 'packeta' ),
				7,
				count( $this->optionsProvider->getStatusSyncingPacketStatuses( $this->packetSynchronizer->getDefaultPacketStatuses() ) ) > 0 &&
				count( $this->optionsProvider->getExistingStatusSyncingOrderStatuses() ) > 0
			),
			new DashboardItem(
				$this->wpAdapter->__( 'Set up automatic shipment submission', 'packeta' ),
				$this->wpAdapter->adminUrl( 'admin.php?page=' . Options\Page::SLUG . '&tab=' . Options\Page::TAB_AUTO_SUBMISSION . '&wizard-enabled=true&wizard-auto-submission-tour-enabled=true' ),
				$this->wpAdapter->__( 'Use this if you want to automatically submit shipments based on the order status.', 'packeta' ),
				8,
				$this->optionsProvider->isPacketAutoSubmissionEnabled() &&
				count( $this->optionsProvider->getPacketAutoSubmissionMappedUniqueEvents() ) > 0
			),
		];
	}

	private function getNewestProductUrl(): ?string {
		$args = array(
			'post_type'      => 'product',
			'posts_per_page' => 1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		$query = new WP_Query( $args );

		if ( $query->have_posts() ) {
			$query->the_post();
			$editUrl = $this->wpAdapter->getEditPostLink( $this->wpAdapter->getTheId(), '' );
			$this->wpAdapter->resetPostdata();

			return $editUrl;
		}

		return null;
	}

	private function getProductSettingsDescription(): string {
		$suffix = '';
		if ( $this->getNewestProductUrl() === null ) {
			$suffix = ' ' . $this->wpAdapter->__( 'First create at least one product.', 'packeta' );
		}

		return $this->wpAdapter->__( 'For each product, you can set it to be intended for adults only, or disable specific Packeta carriers for this product.', 'packeta' ) . $suffix;
	}

	private function getCarrierUpdateUrl(): ?string {
		if ( $this->optionsProvider->get_api_password() === null ) {
			return null;
		}
		if ( $this->carrierUpdater->getLastUpdate() !== null ) {
			return $this->wpAdapter->adminUrl( 'admin.php?page=' . Carrier\OptionsPage::SLUG . '&update_carriers=1' );
		}

		return $this->wpAdapter->adminUrl( 'admin.php?page=' . DashboardPage::SLUG . '&update_carriers=1' );
	}

	private function hasProductsWithPacketaSettings(): bool {
		$args = [
			'post_type'      => 'product',
			'posts_per_page' => 1,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query'     => [
				'relation' => 'OR',
				[
					'key'     => Product\Entity::META_AGE_VERIFICATION_18_PLUS,
					'value'   => '1',
					'compare' => '=',
				],
				[
					'relation' => 'AND',
					[
						'key'     => Product\Entity::META_DISALLOWED_SHIPPING_RATES,
						'compare' => 'EXISTS',
					],
					[
						'key'     => Product\Entity::META_DISALLOWED_SHIPPING_RATES,
						'value'   => 'a:0:{}',
						'compare' => '!=',
					],
				],
			],
		];

		$query = new WP_Query( $args );
		if ( $query->have_posts() ) {
			return true;
		}

		return false;
	}
}
