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
use Packetery\Module\Carrier\CountryListingFormFactory;
use Packetery\Module\CronService;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Log;
use Packetery\Module\ModuleHelper;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Shipping\ShippingMethodGenerator;
use Packetery\Module\Transients;
use Packetery\Module\Views\UrlBuilder;
use Packetery\Nette\Forms\Form;
use Packetery\Nette\Http\Request;

/**
 * Class CountryListingPage
 *
 * @package Packetery\Carrier
 */
class CountryListingPage {

	public const DATA_KEY_COUNTRY_CODE = 'countryCode';
	public const PARAM_CARRIER_FILTER  = 'carrier_query_filter';
	public const PARAM_COUNTRY_FILTER  = 'country_filter';
	public const PARAM_ACTIVE_ONLY     = 'active_carriers_only';

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
	 * @var CarrierUpdater
	 */
	private $carrierUpdater;

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
	private CountryListingFormFactory $countryListingFormFactory;

	/**
	 * @var CarrierActivityBridge
	 */
	private $carrierActivityBridge;
	private WpAdapter $wpAdapter;
	private WcAdapter $wcAdapter;

	public function __construct(
		Engine $latteEngine,
		Repository $carrierRepository,
		CarrierUpdater $carrierUpdater,
		Request $httpRequest,
		OptionsProvider $optionsProvider,
		Log\Page $logPage,
		PacketaPickupPointsConfig $pickupPointsConfig,
		EntityRepository $carrierEntityRepository,
		CarDeliveryConfig $carDeliveryConfig,
		CarrierOptionsFactory $carrierOptionsFactory,
		ModuleHelper $moduleHelper,
		UrlBuilder $urlBuilder,
		CountryListingFormFactory $countryListingFormFactory,
		CarrierActivityBridge $carrierActivityBridge,
		WpAdapter $wpAdapter,
		WcAdapter $wcAdapter
	) {
		$this->latteEngine               = $latteEngine;
		$this->carrierRepository         = $carrierRepository;
		$this->carrierUpdater            = $carrierUpdater;
		$this->httpRequest               = $httpRequest;
		$this->optionsProvider           = $optionsProvider;
		$this->logPage                   = $logPage;
		$this->pickupPointsConfig        = $pickupPointsConfig;
		$this->carrierEntityRepository   = $carrierEntityRepository;
		$this->carDeliveryConfig         = $carDeliveryConfig;
		$this->carrierOptionsFactory     = $carrierOptionsFactory;
		$this->moduleHelper              = $moduleHelper;
		$this->urlBuilder                = $urlBuilder;
		$this->countryListingFormFactory = $countryListingFormFactory;
		$this->carrierActivityBridge     = $carrierActivityBridge;
		$this->wpAdapter                 = $wpAdapter;
		$this->wcAdapter                 = $wcAdapter;
	}

	/**
	 *  Renders page.
	 */
	public function render(): void {
		$redirectUrl = add_query_arg( [ 'page' => OptionsPage::SLUG ], get_admin_url( null, 'admin.php' ) );
		$this->carrierUpdater->startUpdate( $redirectUrl );
		$carriersUpdateParams = $this->carrierUpdater->runUpdate();

		$carriersUpdateParams['link']       = add_query_arg(
			[
				'page'            => OptionsPage::SLUG,
				'update_carriers' => '1',
			],
			get_admin_url( null, 'admin.php' )
		);
		$carriersUpdateParams['lastUpdate'] = $this->carrierUpdater->getLastUpdate();

		$allCountryCodes   = $this->carrierRepository->getCountriesWithUnavailable();
		$internalCountries = $this->pickupPointsConfig->getInternalCountries();
		$allCountryCodes   = array_unique( array_merge( $internalCountries, $allCountryCodes ) );
		$wcCountries       = $this->wcAdapter->countriesGetCountries();
		$countryChoices    = [];
		foreach ( $allCountryCodes as $code ) {
			$countryChoices[ $code ] = $wcCountries[ strtoupper( $code ) ] ?? $code;
		}

		$form = $this->countryListingFormFactory->create( $countryChoices );
		if ( (bool) $form->isSubmitted() ) {
			$form->validate();
		}

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

		$carrierChanges         = get_transient( Transients::CARRIER_CHANGES );
		$settingsChangedMessage = null;
		if ( $carrierChanges !== false ) {
			$settingsChangedMessage = sprintf( // translators: 1: link start 2: link end.
				esc_html__( 'The carrier settings have changed since the last carrier update. %1$sShow logs%2$s', 'packeta' ),
				'<a href="' . $this->logPage->createLogListUrl( null, Record::ACTION_CARRIER_LIST_UPDATE ) . '">',
				'</a>'
			);
		}

		$hasCarriers = false;
		$countries   = $this->getActiveCountries( $form );
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
			$translations['maxWeightValue']     = $this->wpAdapter->__( 'Max. weight/value', 'packeta' );
			$translations['freeShipping']       = $this->wpAdapter->__( 'Free shipping', 'packeta' );
			$translations['ageVerificationFee'] = $this->wpAdapter->__( 'Age verification fee', 'packeta' );
			$translations['maxParcelSize']      = $this->wpAdapter->__( 'Max. parcel size', 'packeta' );
		}

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
	private function getActiveCountries( Form $form ): array {
		$countries = $this->carrierRepository->getCountriesWithUnavailable();

		$internalCountries = $this->pickupPointsConfig->getInternalCountries();
		$countries         = array_unique( array_merge( $internalCountries, $countries ) );

		$selectedCountries = $this->httpRequest->getQuery( self::PARAM_COUNTRY_FILTER );
		if ( is_array( $selectedCountries ) && $selectedCountries !== [] ) {
			$countries = array_intersect( $countries, $selectedCountries );
		}

		$activeOnly = (bool) $this->httpRequest->getQuery( self::PARAM_ACTIVE_ONLY );

		$carrierFilterKeyword = $this->getCarrierFilterKeywordFromForm( $form );

		$countriesFinal = [];
		foreach ( $countries as $country ) {
			$allCarriers = $this->getCarriersDataByCountry( $country, true, $carrierFilterKeyword );
			if ( $activeOnly ) {
				$allCarriers = array_filter(
					$allCarriers,
					fn( array $carrierData ): bool => $carrierData['isActivatedByUser']
				);
			}
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

	private function getCarrierFilterKeywordFromForm( Form $form ): string {
		if ( ! ( (bool) $form->isSubmitted() ) || ! $form->isValid() ) {
			return '';
		}
		$values = (array) $form->getValues( 'array' );
		$value  = $values[ self::PARAM_CARRIER_FILTER ] ?? '';

		return trim( is_string( $value ) ? $value : '' );
	}

	/**
	 * Gets array of carriers data by country code.
	 *
	 * @param string $countryCode Country code.
	 * @param bool   $includeUnavailable Include unavailable carriers.
	 * @param string $carrierFilterKeyword Filter keyword from form (validated min length when filled).
	 *
	 * @return array
	 */
	private function getCarriersDataByCountry( string $countryCode, bool $includeUnavailable, string $carrierFilterKeyword ): array {
		$carriersData    = [];
		$countryCarriers = $this->carrierEntityRepository->getByCountryIncludingNonFeed( $countryCode, $includeUnavailable );
		foreach ( $countryCarriers as $carrier ) {
			if ( $carrier->isCarDelivery() && $this->carDeliveryConfig->isDisabled() ) {
				continue;
			}

			if ( $this->optionsProvider->isWcCarrierConfigEnabled() && ! ShippingMethodGenerator::classExists( $carrier->getId() ) ) {
				continue;
			}

			if ( $carrierFilterKeyword !== '' && stripos( $carrier->getName(), $carrierFilterKeyword ) === false ) {
				continue;
			}

			$carrierId      = $carrier->getId();
			$carrierOptions = $this->carrierOptionsFactory->createByCarrierId( $carrierId );

			$carrierRow = [
				'name'              => $carrier->getName(),
				'isActivatedByUser' => $carrierOptions->isActive(),
				'isActive'          => $this->carrierActivityBridge->isActive( $carrier, $carrierOptions ),
				'detailUrl'         => add_query_arg(
					[
						'page'                            => OptionsPage::SLUG,
						OptionsPage::PARAMETER_CARRIER_ID => $carrierId,
					],
					get_admin_url( null, 'admin.php' )
				),
			];
			if ( $this->optionsProvider->isWcCarrierConfigEnabled() ) {
				$carrierRow['maxWeightValueText']     = $this->formatMaxWeightValueText( $carrierOptions );
				$carrierRow['freeShippingText']       = $this->formatFreeShippingText( $carrierOptions );
				$carrierRow['ageVerificationFeeText'] = $this->formatAgeVerificationFeeText( $carrierOptions );
				$carrierRow['maxParcelSizeText']      = $this->formatMaxParcelSizeText( $carrierOptions );
			}
			$carriersData[ $carrierId ] = $carrierRow;
		}

		return $carriersData;
	}

	private function getCurrencySymbolForDisplay(): string {
		$symbol = $this->wcAdapter->getCurrencySymbol();

		return html_entity_decode( $symbol, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}

	private function formatMaxWeightValueText( Options $carrierOptions ): string {
		$parts     = [];
		$maxWeight = $carrierOptions->getMaxWeightFromLimits();
		if ( $maxWeight !== null && $maxWeight > 0 ) {
			$parts[] = $this->wpAdapter->__( 'up to', 'packeta' ) . ' ' . $maxWeight . ' kg';
		}
		$maxValue = $carrierOptions->getMaxProductValue();
		if ( $maxValue !== null ) {
			$formatted = number_format( $maxValue, 0, ',', ' ' );
			$parts[]   = $this->wpAdapter->__( 'up to', 'packeta' ) . ' ' . $formatted . ' ' . $this->getCurrencySymbolForDisplay();
		}

		return implode( ', ', $parts );
	}

	private function formatFreeShippingText( Options $carrierOptions ): string {
		return $this->formatCurrencyAmount( $carrierOptions->getFreeShippingLimit() );
	}

	private function formatAgeVerificationFeeText( Options $carrierOptions ): string {
		return $this->formatCurrencyAmount( $carrierOptions->getAgeVerificationFee() );
	}

	private function formatMaxParcelSizeText( Options $carrierOptions ): string {
		$restrictions = $carrierOptions->getSizeRestrictions();
		if ( $restrictions === null ) {
			return '';
		}
		if ( isset( $restrictions['length'], $restrictions['width'], $restrictions['height'] ) ) {
			return $restrictions['length'] . 'x' . $restrictions['width'] . 'x' . $restrictions['height'] . ' cm';
		}
		if ( isset( $restrictions['maximum_length'] ) && trim( (string) $restrictions['maximum_length'] ) !== '' ) {
			return $this->wpAdapter->__( 'max', 'packeta' ) . ' ' . $restrictions['maximum_length'] . ' cm';
		}
		if ( isset( $restrictions['dimensions_sum'] ) && trim( (string) $restrictions['dimensions_sum'] ) !== '' ) {
			return $this->wpAdapter->__( 'sum', 'packeta' ) . ' ' . $restrictions['dimensions_sum'] . ' cm';
		}

		return '';
	}

	private function formatCurrencyAmount( ?float $amount ): string {
		if ( $amount === null ) {
			return '';
		}

		return number_format( $amount, 0, ',', ' ' ) . ' ' . $this->getCurrencySymbolForDisplay();
	}
}
