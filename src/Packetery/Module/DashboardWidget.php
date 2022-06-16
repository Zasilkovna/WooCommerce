<?php
/**
 * Class DashboardWidget
 *
 * @package Packetery\Module
 */

declare( strict_types=1 );


namespace Packetery\Module;

use PacketeryLatte\Engine;
use WC_Data_Store;
use WC_Shipping_Zone;

/**
 * Class DashboardWidget
 *
 * @package Packetery\Module
 */
class DashboardWidget {

	/**
	 * Latte engine.
	 *
	 * @var Engine
	 */
	private $latteEngine;

	/**
	 * Carrier repository.
	 *
	 * @var Carrier\Repository
	 */
	private $carrierRepository;

	/**
	 * Options provider.
	 *
	 * @var Options\Provider
	 */
	private $optionsProvider;

	/**
	 * Carrier options page.
	 *
	 * @var Carrier\OptionsPage
	 */
	private $carrierOptionsPage;

	/**
	 * Options page.
	 *
	 * @var Options\Page
	 */
	private $optionsPage;

	/**
	 * Constructor.
	 *
	 * @param Engine              $latteEngine       Latte engine.
	 * @param Carrier\Repository  $carrierRepository Carrier repository.
	 * @param Options\Provider    $optionsProvider   Options provider.
	 * @param Carrier\OptionsPage $carrierOptionsPage Carrier options page.
	 * @param Options\Page        $optionsPage Options page.
	 */
	public function __construct(
		Engine $latteEngine,
		Carrier\Repository $carrierRepository,
		Options\Provider $optionsProvider,
		Carrier\OptionsPage $carrierOptionsPage,
		Options\Page $optionsPage
	) {
		$this->latteEngine        = $latteEngine;
		$this->carrierRepository  = $carrierRepository;
		$this->optionsProvider    = $optionsProvider;
		$this->carrierOptionsPage = $carrierOptionsPage;
		$this->optionsPage        = $optionsPage;
	}

	/**
	 * Registers widget.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_dashboard_setup', [ $this, 'setup' ] );
	}

	/**
	 * Dashborad setup.
	 *
	 * @return void
	 */
	public function setup(): void {
		wp_add_dashboard_widget( 'packetery_dashboard_widget', __( 'Packeta', 'packeta' ), [ $this, 'render' ], null, null, 'normal', 'high' );
	}

	/**
	 * Tells if there is Packeta shipping method configured and active.
	 *
	 * @return bool
	 */
	private function isPacketaShippingMethodActive(): bool {
		$shippingDataStore = WC_Data_Store::load( 'shipping-zone' );
		$shippingZones     = $shippingDataStore->get_zones();

		foreach ( $shippingZones as $shippingZoneId ) {
			$shippingZone        = new WC_Shipping_Zone( $shippingZoneId );
			$shippingZoneMethods = $shippingZone->get_shipping_methods( true );
			foreach ( $shippingZoneMethods as $method ) {
				if ( ShippingMethod::PACKETERY_METHOD_ID === $method->id ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Renders widget.
	 *
	 * @return void
	 */
	public function render(): void {
		$wcCountries        = WC()->countries->get_countries();
		$activeCountries    = [];
		$isCodSettingNeeded = false;

		foreach ( $this->carrierRepository->getAllCarriersIncludingZpoints() as $carrier ) {
			$country        = $carrier->getCountry();
			$carrierOptions = Carrier\Options::createByCarrierId( $carrier->getId() );

			if ( false === $carrierOptions->isActive() ) {
				continue;
			}

			if ( false === $isCodSettingNeeded && $this->optionsProvider->getCodPaymentMethod() === null && $carrierOptions->hasAnyCodSurchargeSetting() ) {
				$isCodSettingNeeded = true;
			}

			if ( ! isset( $activeCountries[ $country ] ) ) {
				$activeCountries[ $country ] = [
					'code' => $country,
					'name' => $wcCountries[ strtoupper( $country ) ],
					'url'  => $this->carrierOptionsPage->createUrl( $country ),
				];
			}
		}

		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/dashboard-widget.latte',
			[
				'activeCountries'      => $activeCountries,
				'activeCountriesCount' => count( $activeCountries ),
				'isCodSettingNeeded'   => $isCodSettingNeeded,
				'hasExternalCarrier'   => $this->carrierRepository->hasAnyActiveFeedCarrier(),
				'hasPacketaShipping'   => $this->isPacketaShippingMethodActive(),
				'translations'         => [
					'activeCountryCountNotice' => __( 'Active country count', 'packeta' ),
					'activeCountriesNotice'    => __( 'You can now send goods to the following countries via Packeta', 'packeta' ),
					'noActiveCountry'          => sprintf(
						// translators: 1: link start 2: link end.
						esc_html__( 'Now you do not send parcels to any country via Packeta. The settings can be made %1$shere%2$s.', 'packeta' ),
						sprintf( '<a href="%s">', $this->carrierOptionsPage->createUrl() ),
						'</a>'
					),
					'noCodPaymentConfigured'   => sprintf(
						// translators: 1: link start 2: link end.
						esc_html__( 'No COD payment configured. The settings can be made %1$shere%2$s.', 'packeta' ),
						sprintf( '<a href="%s">', $this->optionsPage->createUrl() ),
						'</a>'
					),
					'noExternalCarrier'        => sprintf(
						// translators: 1: link start 2: link end.
						esc_html__( 'No external carrier was found. Carriers can be downloaded %1$shere%2$s.', 'packeta' ),
						sprintf( '<a href="%s">', $this->carrierOptionsPage->createUrl() ),
						'</a>'
					),
					'noPacketaShipping'        => sprintf(
						// translators: 1: link start 2: link end.
						esc_html__( 'No Packeta shipping method was configured. Configure shipping zone with Packeta shipping method %1$shere%2$s.', 'packeta' ),
						sprintf(
							'<a href="%s">',
							add_query_arg(
								[
									'page' => 'wc-settings',
									'tab'  => 'shipping',
								],
								get_admin_url( null, 'admin.php' )
							)
						),
						'</a>'
					),
				],
			]
		);
	}
}
