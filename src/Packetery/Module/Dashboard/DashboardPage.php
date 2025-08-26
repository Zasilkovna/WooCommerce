<?php

declare( strict_types=1 );

namespace Packetery\Module\Dashboard;

use Packetery\Latte\Engine;
use Packetery\Module\Carrier\CarrierUpdater;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\ModuleHelper;
use Packetery\Module\Views\UrlBuilder;

class DashboardPage {

	public const SLUG = 'packeta-home';

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * @var Engine
	 */
	private $latteEngine;

	/**
	 * @var ModuleHelper
	 */
	private $moduleHelper;

	/**
	 * @var UrlBuilder
	 */
	private $urlBuilder;

	/**
	 * @var DashboardItemBuilder
	 */
	private $dashboardItemBuilder;

	/**
	 * @var CarrierUpdater
	 */
	private $carrierUpdater;

	public function __construct(
		WpAdapter $wpAdapter,
		Engine $latteEngine,
		ModuleHelper $moduleHelper,
		UrlBuilder $urlBuilder,
		DashboardItemBuilder $dashboardItemBuilder,
		CarrierUpdater $carrierUpdater
	) {
		$this->wpAdapter            = $wpAdapter;
		$this->latteEngine          = $latteEngine;
		$this->moduleHelper         = $moduleHelper;
		$this->urlBuilder           = $urlBuilder;
		$this->dashboardItemBuilder = $dashboardItemBuilder;
		$this->carrierUpdater       = $carrierUpdater;
	}

	public function register(): void {
		$this->wpAdapter->addSubmenuPage(
			self::SLUG,
			$this->wpAdapter->__( 'Home', 'packeta' ),
			$this->wpAdapter->__( 'Home', 'packeta' ),
			'manage_woocommerce',
			self::SLUG,
			[ $this, 'render' ]
		);
	}

	public function render(): void {
		$redirectUrl = $this->wpAdapter->addQueryArg(
			[ 'page' => self::SLUG ],
			$this->wpAdapter->getAdminUrl( null, 'admin.php' )
		);
		$this->carrierUpdater->startUpdate( $redirectUrl );
		$carriersUpdateResult = $this->carrierUpdater->runUpdate();

		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/dashboard/home.latte',
			[
				'items'          => $this->dashboardItemBuilder->buildItems(),
				'carriersUpdate' => $carriersUpdateResult,
				'isCzechLocale'  => $this->moduleHelper->isCzechLocale(),
				'logoZasilkovna' => $this->urlBuilder->buildAssetUrl( 'public/images/logo-zasilkovna.svg' ),
				'logoPacketa'    => $this->urlBuilder->buildAssetUrl( 'public/images/logo-packeta.svg' ),

				'translations'   => [
					'packeta'    => $this->wpAdapter->__( 'Packeta', 'packeta' ),
					'title'      => $this->wpAdapter->__( 'Let\'s start with the plugin settings', 'packeta' ),
					'getTheMost' => $this->wpAdapter->__( 'Follow the steps below to get the most out of Packeta', 'packeta' ),
				],
			]
		);
	}
}
