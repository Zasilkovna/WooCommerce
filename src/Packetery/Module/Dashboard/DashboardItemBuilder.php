<?php

declare( strict_types=1 );

namespace Packetery\Module\Dashboard;

use Packetery\Module\Carrier;
use Packetery\Module\Carrier\CarrierUpdater;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options;
use Packetery\Module\Options\OptionsProvider;
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

	public function __construct(
		WpAdapter $wpAdapter,
		DashboardHelper $dashboardHelper,
		OptionsProvider $optionsProvider,
		Carrier\EntityRepository $carrierEntityRepository,
		CarrierUpdater $carrierUpdater
	) {
		$this->wpAdapter               = $wpAdapter;
		$this->dashboardHelper         = $dashboardHelper;
		$this->optionsProvider         = $optionsProvider;
		$this->carrierEntityRepository = $carrierEntityRepository;
		$this->carrierUpdater          = $carrierUpdater;
	}

	/**
	 * @return DashboardItem[]
	 */
	public function buildItems(): array {
		return [
			new DashboardItem(
				$this->wpAdapter->__( 'Basic settings of the Packeta plugin', 'packeta' ),
				$this->wpAdapter->adminUrl( 'admin.php?page=' . Options\Page::SLUG . '&wizard-enabled=true&wizard-general-settings-tour-enabled=true' ),
				'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Nullam eget nisl. Integer in sapien.',
				1,
				$this->optionsProvider->get_api_password() !== null && $this->optionsProvider->get_sender() !== null
			),
			new DashboardItem(
				$this->wpAdapter->__( 'Carrier type setting', 'packeta' ),
				$this->wpAdapter->adminUrl( 'admin.php?page=' . Options\Page::SLUG . '&tab=advanced&wizard-enabled=true&wizard-advanced-tour-enabled=true' ),
				'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Nullam eget nisl. Integer in sapien.',
				2,
				$this->optionsProvider->isWcCarrierConfigEnabledNullable() !== null
			),
			new DashboardItem(
				$this->wpAdapter->__( 'Product settings', 'packeta' ),
				$this->getNewestProductUrl(),
				$this->getProductSettingsDescription(),
				3,
				false
			),
			new DashboardItem(
				$this->wpAdapter->__( 'Carriers update', 'packeta' ),
				$this->getCarrierUpdateUrl(),
				'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Nullam eget nisl. Integer in sapien.',
				4,
				$this->carrierUpdater->getLastUpdate() !== null
			),
			new DashboardItem(
				$this->wpAdapter->__( 'Carrier settings', 'packeta' ),
				$this->wpAdapter->adminUrl( 'admin.php?page=' . Carrier\OptionsPage::SLUG ),
				'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Nullam eget nisl. Integer in sapien.',
				5,
				count( $this->carrierEntityRepository->getAllActiveCarriersList() ) > 0
			),
			new DashboardItem(
				$this->wpAdapter->__( 'Shipping zone settings', 'packeta' ),
				'',
				'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Nullam eget nisl. Integer in sapien.',
				6,
				$this->dashboardHelper->isPacketaShippingMethodActive()
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
}
