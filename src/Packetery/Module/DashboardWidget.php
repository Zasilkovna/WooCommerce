<?php
/**
 * Class DashboardWidget
 *
 * @package Packetery\Module
 */

declare( strict_types=1 );


namespace Packetery\Module;

use Packetery\Latte\Engine;
use Packetery\Module\Carrier\CarrierOptionsFactory;
use Packetery\Module\Carrier\CountryListingPage;
use Packetery\Module\Options\OptionsProvider;
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
	 * @var OptionsProvider
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
	 * Survey config.
	 *
	 * @var array
	 */
	private $surveyConfig;

	/**
	 * Carrier entity repository.
	 *
	 * @var Carrier\EntityRepository
	 */
	private $carrierEntityRepository;

	/**
	 * ModuleHelper.
	 *
	 * @var ModuleHelper
	 */
	private $moduleHelper;

	/**
	 * Carrier options factory.
	 *
	 * @var CarrierOptionsFactory
	 */
	private $carrierOptionsFactory;

	/**
	 * Constructor.
	 *
	 * @param Engine                   $latteEngine             Latte engine.
	 * @param Carrier\Repository       $carrierRepository       Carrier repository.
	 * @param OptionsProvider          $optionsProvider         Options provider.
	 * @param Carrier\OptionsPage      $carrierOptionsPage      Carrier options page.
	 * @param Options\Page             $optionsPage             Options page.
	 * @param array                    $surveyConfig            Survey config.
	 * @param Carrier\EntityRepository $carrierEntityRepository Carrier repository.
	 * @param ModuleHelper             $moduleHelper            ModuleHelper.
	 * @param CarrierOptionsFactory    $carrierOptionsFactory   Carrier options factory.
	 */
	public function __construct(
		Engine $latteEngine,
		Carrier\Repository $carrierRepository,
		OptionsProvider $optionsProvider,
		Carrier\OptionsPage $carrierOptionsPage,
		Options\Page $optionsPage,
		array $surveyConfig,
		Carrier\EntityRepository $carrierEntityRepository,
		ModuleHelper $moduleHelper,
		CarrierOptionsFactory $carrierOptionsFactory
	) {
		$this->latteEngine             = $latteEngine;
		$this->carrierRepository       = $carrierRepository;
		$this->optionsProvider         = $optionsProvider;
		$this->carrierOptionsPage      = $carrierOptionsPage;
		$this->optionsPage             = $optionsPage;
		$this->surveyConfig            = $surveyConfig;
		$this->carrierEntityRepository = $carrierEntityRepository;
		$this->moduleHelper            = $moduleHelper;
		$this->carrierOptionsFactory   = $carrierOptionsFactory;
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

		foreach ( $this->carrierEntityRepository->getAllCarriersIncludingNonFeed() as $carrier ) {
			$country        = $carrier->getCountry();
			$carrierOptions = $this->carrierOptionsFactory->createByCarrierId( $carrier->getId() );

			if ( false === $carrierOptions->isActive() ) {
				continue;
			}

			if ( false === $isCodSettingNeeded && $this->optionsProvider->getCodPaymentMethods() === [] && $carrierOptions->hasAnyCodSurchargeSetting() ) {
				$isCodSettingNeeded = true;
			}

			if ( ! isset( $activeCountries[ $country ] ) ) {
				$activeCountries[ $country ] = [
					CountryListingPage::DATA_KEY_COUNTRY_CODE => $country,
					'name' => $wcCountries[ strtoupper( $country ) ],
					'url'  => $this->carrierOptionsPage->createUrl( $country ),
				];
			}
		}

		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/dashboard-widget.latte',
			[
				'activeCountries'    => $activeCountries,
				'isCodSettingNeeded' => $isCodSettingNeeded,
				'isOptionsFormValid' => $this->optionsPage->create_form()->isValid(),
				'hasExternalCarrier' => $this->carrierRepository->hasAnyActiveFeedCarrier(),
				'hasPacketaShipping' => $this->isPacketaShippingMethodActive(),
				'survey'             => new SurveyConfig(
					( $this->surveyConfig['active'] && new \DateTimeImmutable( 'now' ) <= $this->surveyConfig['validTo'] ),
					$this->surveyConfig['url'],
					Plugin::buildAssetUrl( 'public/images/survey-illustration.png' )
				),
				'translations'       => [
					'packeta'                => __( 'Packeta', 'packeta' ),
					'activeCountriesNotice'  => __( 'You have set up Packeta carriers for the following countries', 'packeta' ),
					'noGlobalSettings'       => sprintf(
						// translators: 1: link start 2: link end.
						esc_html__( 'Global plugin settings have not been made, you can make the settings %1$shere%2$s.', 'packeta' ),
						...$this->moduleHelper->createLinkParts( $this->optionsPage->createLink() )
					),
					'noActiveCountry'        => sprintf(
						// translators: 1: link start 2: link end.
						esc_html__( 'Now you do not send parcels to any country via Packeta. The settings can be made %1$shere%2$s.', 'packeta' ),
						...$this->moduleHelper->createLinkParts( $this->carrierOptionsPage->createUrl() )
					),
					'noCodPaymentConfigured' => sprintf(
						// translators: 1: link start 2: link end.
						esc_html__( 'No COD payment configured. The settings can be made %1$shere%2$s.', 'packeta' ),
						...$this->moduleHelper->createLinkParts( $this->optionsPage->createLink() )
					),
					'noExternalCarrier'      => sprintf(
						// translators: 1: link start 2: link end.
						esc_html__( 'No external carrier was found. Carriers can be downloaded %1$shere%2$s.', 'packeta' ),
						...$this->moduleHelper->createLinkParts( $this->carrierOptionsPage->createUrl() )
					),
					'noPacketaShipping'      => sprintf(
						// translators: 1: link start 2: link end.
						esc_html__( 'No Packeta shipping method was configured. Configure shipping zone with Packeta shipping method %1$shere%2$s.', 'packeta' ),
						...$this->moduleHelper->createLinkParts(
							add_query_arg(
								[
									'page' => 'wc-settings',
									'tab'  => 'shipping',
								],
								get_admin_url( null, 'admin.php' )
							)
						)
					),
					'surveyTitle'            => __( 'Help us with plugin development', 'packeta' ),
					'surveyDescription'      => __( 'An effective way to improve our plugin is to ask you, its users, for your opinion. It won\'t take you even two minutes and it will help us a lot to develop the plugin in the right way. Thank you very much.', 'packeta' ),
					'surveyButtonText'       => __( 'Fill out a questionnaire', 'packeta' ),
				],
			]
		);
	}
}
