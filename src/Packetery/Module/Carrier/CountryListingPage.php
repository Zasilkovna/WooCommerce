<?php
/**
 * Class CountryListingPage
 *
 * @package Packetery\Carrier
 */

declare( strict_types=1 );

namespace Packetery\Module\Carrier;

use Packetery\Core\Log\Record;
use Packetery\Latte\Engine;
use Packetery\Module\CronService;
use Packetery\Module\FormFactory;
use Packetery\Module\Log;
use Packetery\Module\ModuleHelper;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Shipping\ShippingMethodGenerator;
use Packetery\Module\Views\UrlBuilder;
use Packetery\Nette\Forms\Form;
use Packetery\Nette\Http\Request;

/**
 * Class CountryListingPage
 *
 * @package Packetery\Carrier
 */
class CountryListingPage {

	public const TRANSIENT_CARRIER_CHANGES = 'packetery_carrier_changes';
	public const DATA_KEY_COUNTRY_CODE     = 'countryCode';
	public const PARAM_CARRIER_FILTER      = 'carrier_query_filter';

	/**
	 * PacketeryLatteEngine.
	 *
	 * @var Engine PacketeryLatte engine.
	 */
	private $latteEngine;

	/**
	 * Carrier repository.
	 *
	 * @var Repository Carrier repository.
	 */
	private $carrierRepository;

	/**
	 * Carrier downloader.
	 *
	 * @var Downloader
	 */
	private $downloader;

	/**
	 * Http request.
	 *
	 * @var Request
	 */
	private $httpRequest;

	/**
	 * Options provider
	 *
	 * @var OptionsProvider
	 */
	private $optionsProvider;

	/**
	 * Log Page
	 *
	 * @var Log\Page
	 */
	private $logPage;

	/**
	 * Internal pickup points config.
	 *
	 * @var PacketaPickupPointsConfig
	 */
	private $pickupPointsConfig;

	/**
	 * Carrier entity repository.
	 *
	 * @var EntityRepository
	 */
	private $carrierEntityRepository;

	/**
	 * Car delivery config.
	 *
	 * @var CarDeliveryConfig
	 */
	private $carDeliveryConfig;

	/**
	 * Carrier options factory.
	 *
	 * @var CarrierOptionsFactory
	 */
	private $carrierOptionsFactory;

	/**
	 * @var ModuleHelper
	 */
	private $moduleHelper;

	/**
	 * @var UrlBuilder
	 */
	private $urlBuilder;

	/**
	 * @var FormFactory
	 */
	private $formFactory;

	/**
	 * @var CarrierActivityBridge
	 */
	private $carrierActivityBridge;

	public function __construct(
		Engine $latteEngine,
		Repository $carrierRepository,
		Downloader $downloader,
		Request $httpRequest,
		OptionsProvider $optionsProvider,
		Log\Page $logPage,
		PacketaPickupPointsConfig $pickupPointsConfig,
		EntityRepository $carrierEntityRepository,
		CarDeliveryConfig $carDeliveryConfig,
		CarrierOptionsFactory $carrierOptionsFactory,
		ModuleHelper $moduleHelper,
		UrlBuilder $urlBuilder,
		FormFactory $formFactory,
		CarrierActivityBridge $carrierActivityBridge
	) {
		$this->latteEngine             = $latteEngine;
		$this->carrierRepository       = $carrierRepository;
		$this->downloader              = $downloader;
		$this->httpRequest             = $httpRequest;
		$this->optionsProvider         = $optionsProvider;
		$this->logPage                 = $logPage;
		$this->pickupPointsConfig      = $pickupPointsConfig;
		$this->carrierEntityRepository = $carrierEntityRepository;
		$this->carDeliveryConfig       = $carDeliveryConfig;
		$this->carrierOptionsFactory   = $carrierOptionsFactory;
		$this->moduleHelper            = $moduleHelper;
		$this->urlBuilder              = $urlBuilder;
		$this->formFactory             = $formFactory;
		$this->carrierActivityBridge   = $carrierActivityBridge;
	}

	/**
	 *  Renders page.
	 */
	public function render(): void {
		$carriersUpdateParams = [];
		if ( $this->httpRequest->getQuery( 'update_carriers' ) !== null ) {
			set_transient( 'packetery_run_update_carriers', true );
			if ( wp_safe_redirect( add_query_arg( [ 'page' => OptionsPage::SLUG ], get_admin_url( null, 'admin.php' ) ) ) ) {
				exit;
			}
		}
		if ( get_transient( 'packetery_run_update_carriers' ) !== false ) {
			[ $carrierUpdaterResult, $carrierUpdaterClass ] = $this->downloader->run();
			$carriersUpdateParams                           = [
				'result'      => $carrierUpdaterResult,
				'resultClass' => $carrierUpdaterClass,
			];
			delete_transient( 'packetery_run_update_carriers' );
		}

		$carriersUpdateParams['link']       = add_query_arg(
			[
				'page'            => OptionsPage::SLUG,
				'update_carriers' => '1',
			],
			get_admin_url( null, 'admin.php' )
		);
		$carriersUpdateParams['lastUpdate'] = $this->getLastUpdate();

		$form = $this->formFactory->create();
		$form->setAction( 'admin.php?page=' . OptionsPage::SLUG );
		$form->setMethod( Form::GET );
		$form->addText( self::PARAM_CARRIER_FILTER );
		$form->addSubmit( 'filter', __( 'Filter', 'packeta' ) );

		$isApiPasswordSet = false;
		if ( $this->optionsProvider->get_api_password() !== null ) {
			$isApiPasswordSet = true;
		}

		$nextScheduledRun          = null;
		$nextScheduledRunTimestamp = as_next_scheduled_action( CronService::CRON_CARRIERS_HOOK );
		if ( is_int( $nextScheduledRunTimestamp ) ) {
			$date = new \DateTime();
			$date->setTimezone( wp_timezone() );
			$date->setTimestamp( $nextScheduledRunTimestamp );
			$nextScheduledRun = $date->format( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
		}

		$carrierChanges         = get_transient( self::TRANSIENT_CARRIER_CHANGES );
		$settingsChangedMessage = null;
		if ( $carrierChanges !== false ) {
			$settingsChangedMessage = sprintf( // translators: 1: link start 2: link end.
				esc_html__( 'The carrier settings have changed since the last carrier update. %1$sShow logs%2$s', 'packeta' ),
				'<a href="' . $this->logPage->createLogListUrl( null, Record::ACTION_CARRIER_LIST_UPDATE ) . '">',
				'</a>'
			);
		}

		$hasCarriers = false;
		$countries   = $this->getActiveCountries();
		foreach ( $countries as $country ) {
			if ( isset( $country['allCarriers'] ) ) {
				$hasCarriers = true;

				break;
			}
		}

		$translations = [
			'packeta'                    => __( 'Packeta', 'packeta' ),
			'title'                      => __( 'Carriers', 'packeta' ),
			'carriersUpdate'             => __( 'Carriers update', 'packeta' ),
			'countries'                  => __( 'Countries', 'packeta' ),
			'activeCarrier'              => __( 'Active carrier', 'packeta' ),
			'action'                     => __( 'Action', 'packeta' ),
			'countryName'                => __( 'Country name', 'packeta' ),
			'carrier'                    => __( 'Carrier', 'packeta' ),
			'countryCode'                => __( 'Country code', 'packeta' ),
			'setUp'                      => __( 'Set up', 'packeta' ),
			'status'                     => __( 'Status', 'packeta' ),
			'active'                     => __( 'Active', 'packeta' ),
			'inactive'                   => __( 'Inactive', 'packeta' ),
			'noActiveCountries'          => __( 'No active countries.', 'packeta' ),
			'noCarriersFound'            => __( 'No results found!', 'packeta' ),
			'searchPlaceholder'          => __( 'Search carriers', 'packeta' ),
			'lastCarrierUpdateDatetime'  => __( 'Date of the last update of carriers', 'packeta' ),
			'carrierListNeverDownloaded' => __( 'Carrier list was not yet downloaded. Continue by clicking the Run update of carriers button.', 'packeta' ),
			'runCarrierUpdate'           => __( 'Run update of carriers', 'packeta' ),
			'pleaseCompleteSetupFirst'   => __( 'Before updating the carriers, please complete the plugin setup first.', 'packeta' ),
			'nextScheduledRunPlannedAt'  => __( 'The next automatic update will occur at', 'packeta' ),
		];

		if ( $this->optionsProvider->isWcCarrierConfigEnabled() ) {
			$settingsTemplate = PACKETERY_PLUGIN_DIR . '/template/carrier/wcNativeSettings.latte';
		} else {
			$settingsTemplate = PACKETERY_PLUGIN_DIR . '/template/carrier/countries.latte';
		}

		$this->latteEngine->render(
			$settingsTemplate,
			new CountryListingTemplateParams(
				$carriersUpdateParams,
				$countries,
				$isApiPasswordSet,
				$nextScheduledRun,
				$settingsChangedMessage,
				$this->moduleHelper->isCzechLocale(),
				$this->urlBuilder->buildAssetUrl( 'public/images/logo-zasilkovna.svg' ),
				$this->urlBuilder->buildAssetUrl( 'public/images/logo-packeta.svg' ),
				$translations,
				$hasCarriers,
				$form
			)
		);
	}

	/**
	 * Returns Sorted data for template.
	 *
	 * @return array Data.
	 */
	private function getActiveCountries(): array {
		$countries = $this->carrierRepository->getCountries();

		$internalCountries = $this->pickupPointsConfig->getInternalCountries();
		$countries         = array_unique( array_merge( $internalCountries, $countries ) );

		$countriesFinal = [];
		foreach ( $countries as $country ) {
			$allCarriers      = $this->getCarriersDataByCountry( $country );
			$activeCarriers   = array_filter(
				$allCarriers,
				static function ( array $carrierData ): bool {
					return $carrierData['isActive'];
				}
			);
			$wcCountries      = \WC()->countries->get_countries();
			$countriesFinal[] = [
				self::DATA_KEY_COUNTRY_CODE => $country,
				'name'                      => $wcCountries[ strtoupper( $country ) ],
				'url'                       => add_query_arg(
					[
						'page' => OptionsPage::SLUG,
						OptionsPage::PARAMETER_COUNTRY_CODE => $country,
					],
					get_admin_url( null, 'admin.php' )
				),
				'activeCarriers'            => $activeCarriers,
				'allCarriers'               => $allCarriers,
				'flag'                      => $this->urlBuilder->buildAssetUrl( sprintf( 'public/images/flags/%s.png', $country ) ),
			];
		}

		usort(
			$countriesFinal,
			static function ( $a, $b ) {
				if ( $a[ self::DATA_KEY_COUNTRY_CODE ] === 'cz' ) {
					return - 1;
				}
				if ( $b[ self::DATA_KEY_COUNTRY_CODE ] === 'cz' ) {
					return 1;
				}
				if ( $a[ self::DATA_KEY_COUNTRY_CODE ] === 'sk' ) {
					return - 1;
				}
				if ( $b[ self::DATA_KEY_COUNTRY_CODE ] === 'sk' ) {
					return 1;
				}

				return strnatcmp( (string) $a['name'], (string) $b['name'] );
			}
		);

		return $countriesFinal;
	}

	/**
	 * Gets last update datetime.
	 *
	 * @return string|null
	 */
	public function getLastUpdate(): ?string {
		$lastCarrierUpdate = get_option( Downloader::OPTION_LAST_CARRIER_UPDATE );
		if ( $lastCarrierUpdate !== false ) {
			$date = \DateTime::createFromFormat( DATE_ATOM, $lastCarrierUpdate );
			if ( $date !== false ) {
				$date->setTimezone( wp_timezone() );

				return $date->format( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
			}
		}

		return null;
	}

	/**
	 * Gets options of active carriers.
	 *
	 * @return array
	 */
	public function getCarriersForOptionsExport(): array {
		$carriersWithSomeOptions = [];
		$allCarriers             = $this->carrierEntityRepository->getAllCarriersIncludingNonFeed();
		foreach ( $allCarriers as $carrier ) {
			$carrierId      = $carrier->getId();
			$optionId       = OptionPrefixer::getOptionId( $carrierId );
			$carrierOptions = get_option( $optionId );
			if ( $carrierOptions !== false ) {
				unset( $carrierOptions['id'] );
				$originalName = $carrier->getName();
				$cartName     = $carrierOptions['name'];
				unset( $carrierOptions['name'] );
				$addition                             = [
					'original_name' => $originalName,
					'cart_name'     => $cartName,
				];
				$carrierOptions                       = array_merge( $addition, $carrierOptions );
				$carriersWithSomeOptions[ $optionId ] = $carrierOptions;
			}
		}

		return $carriersWithSomeOptions;
	}

	/**
	 * Gets array of carriers data by country code.
	 *
	 * @param string $countryCode Country code.
	 *
	 * @return array
	 */
	private function getCarriersDataByCountry( string $countryCode ): array {
		$carriersData    = [];
		$keyword         = $this->httpRequest->getQuery( self::PARAM_CARRIER_FILTER ) ?? '';
		$countryCarriers = $this->carrierEntityRepository->getByCountryIncludingNonFeed( $countryCode );
		foreach ( $countryCarriers as $carrier ) {
			if ( $carrier->isCarDelivery() && $this->carDeliveryConfig->isDisabled() ) {
				continue;
			}

			if ( $this->optionsProvider->isWcCarrierConfigEnabled() && ! ShippingMethodGenerator::classExists( $carrier->getId() ) ) {
				continue;
			}

			if ( $keyword !== '' && stripos( $carrier->getName(), $keyword ) === false ) {
				continue;
			}

			$carrierId      = $carrier->getId();
			$carrierOptions = $this->carrierOptionsFactory->createByCarrierId( $carrierId );

			$carriersData[ $carrierId ] = [
				'name'              => $carrier->getName(),
				'isActivatedByUser' => $carrierOptions->isActive(),
				'isActive'          => $this->carrierActivityBridge->isActive( $carrier->getId(), $carrierOptions ),
				'detailUrl'         => add_query_arg(
					[
						'page'                            => OptionsPage::SLUG,
						OptionsPage::PARAMETER_CARRIER_ID => $carrierId,
					],
					get_admin_url( null, 'admin.php' )
				),
			];
		}

		return $carriersData;
	}
}
