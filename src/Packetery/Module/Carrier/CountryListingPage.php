<?php
/**
 * Class CountryListingPage
 *
 * @package Packetery\Carrier
 */

declare( strict_types=1 );

namespace Packetery\Module\Carrier;

use Packetery\Core\Log\Record;
use Packetery\Module\CronService;
use Packetery\Module\Log;
use Packetery\Module\Options\Provider;
use PacketeryLatte\Engine;
use PacketeryNette\Http\Request;
use Packetery\Module\Plugin;

/**
 * Class CountryListingPage
 *
 * @package Packetery\Carrier
 */
class CountryListingPage {

	public const TRANSIENT_CARRIER_CHANGES = 'packetery_carrier_changes';

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
	 * @var Provider
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
	 * CountryListingPage constructor.
	 *
	 * @param Engine                    $latteEngine             PacketeryLatte engine.
	 * @param Repository                $carrierRepository       Carrier repository.
	 * @param Downloader                $downloader              Carrier downloader.
	 * @param Request                   $httpRequest             Http request.
	 * @param Provider                  $optionsProvider         Options provider.
	 * @param Log\Page                  $logPage                 Log page.
	 * @param PacketaPickupPointsConfig $pickupPointsConfig      Internal pickup points config.
	 * @param EntityRepository          $carrierEntityRepository Carrier repository.
	 */
	public function __construct(
		Engine $latteEngine,
		Repository $carrierRepository,
		Downloader $downloader,
		Request $httpRequest,
		Provider $optionsProvider,
		Log\Page $logPage,
		PacketaPickupPointsConfig $pickupPointsConfig,
		EntityRepository $carrierEntityRepository
	) {
		$this->latteEngine             = $latteEngine;
		$this->carrierRepository       = $carrierRepository;
		$this->downloader              = $downloader;
		$this->httpRequest             = $httpRequest;
		$this->optionsProvider         = $optionsProvider;
		$this->logPage                 = $logPage;
		$this->pickupPointsConfig      = $pickupPointsConfig;
		$this->carrierEntityRepository = $carrierEntityRepository;
	}

	/**
	 *  Renders page.
	 */
	public function render(): void {
		$carriersUpdateParams = [];
		if ( $this->httpRequest->getQuery( 'update_carriers' ) ) {
			set_transient( 'packetery_run_update_carriers', true );
			if ( wp_safe_redirect( add_query_arg( [ 'page' => OptionsPage::SLUG ], get_admin_url( null, 'admin.php' ) ) ) ) {
				exit;
			}
		}
		if ( get_transient( 'packetery_run_update_carriers' ) ) {
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

		$isApiPasswordSet = false;
		if ( null !== $this->optionsProvider->get_api_password() ) {
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
		if ( $carrierChanges ) {
			$settingsChangedMessage = sprintf( // translators: 1: link start 2: link end.
				esc_html__( 'The carrier settings have changed since the last carrier update. %1$sShow logs%2$s', 'packeta' ),
				'<a href="' . $this->logPage->createLogListUrl( null, Record::ACTION_CARRIER_LIST_UPDATE ) . '">',
				'</a>'
			);
		}

		$countries = $this->getActiveCountries();
		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/carrier/countries.latte',
			[
				'carriersUpdate'         => $carriersUpdateParams,
				'countries'              => $countries,
				'isApiPasswordSet'       => $isApiPasswordSet,
				'nextScheduledRun'       => $nextScheduledRun,
				'settingsChangedMessage' => $settingsChangedMessage,
				'translations'           => [
					'packeta'                        => __( 'Packeta', 'packeta' ),
					'title'                          => __( 'Carriers', 'packeta' ),
					'carriersUpdate'                 => __( 'Carriers update', 'packeta' ),
					'countries'                      => __( 'Countries', 'packeta' ),
					'activeCarrier'                  => __( 'Active carrier', 'packeta' ),
					'action'                         => __( 'Action', 'packeta' ),
					'countryName'                    => __( 'Country name', 'packeta' ),
					'countryCode'                    => __( 'Country code', 'packeta' ),
					'setUp'                          => __( 'Set up', 'packeta' ),
					'noActiveCountries'              => __( 'No active countries.', 'packeta' ),
					'lastCarrierUpdateDatetime'      => __( 'Date of the last update of carriers', 'packeta' ),
					'carrierListNeverDownloaded'     => __( 'Carrier list was not yet downloaded. Continue by clicking the Run update of carriers button.', 'packeta' ),
					'runCarrierUpdate'               => __( 'Run update of carriers', 'packeta' ),
					'completeSetupBeforeUpdate'      => __( 'Before updating the carriers, please complete the plugin setup first.', 'packeta' ),
					'completeSetupBeforeSetCarriers' => __( 'Before setting the carriers options, please complete the plugin setup first.', 'packeta' ),
					'nextScheduledRunPlannedAt'      => __( 'The next automatic update will occur at', 'packeta' ),
				],
			]
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
			$activeCarriers   = $this->getActiveCarriersNamesByCountry( $country );
			$wcCountries      = \WC()->countries->get_countries();
			$countriesFinal[] = [
				'code'           => $country,
				'name'           => $wcCountries[ strtoupper( $country ) ],
				'url'            => add_query_arg(
					[
						'page' => OptionsPage::SLUG,
						'code' => $country,
					],
					get_admin_url( null, 'admin.php' )
				),
				'activeCarriers' => $activeCarriers,
				'flag'           => Plugin::buildAssetUrl( sprintf( 'public/images/flags/%s.png', $country ) ),
			];
		}

		usort(
			$countriesFinal,
			static function ( $a, $b ) {
				if ( 'cz' === $a['code'] ) {
					return - 1;
				}
				if ( 'cz' === $b['code'] ) {
					return 1;
				}
				if ( 'sk' === $a['code'] ) {
					return - 1;
				}
				if ( 'sk' === $b['code'] ) {
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
		if ( false !== $lastCarrierUpdate ) {
			$date = \DateTime::createFromFormat( DATE_ATOM, $lastCarrierUpdate );
			if ( false !== $date ) {
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
			if ( false !== $carrierOptions ) {
				unset( $carrierOptions['id'] );
				$originalName = $carrier->getName();
				$cartName     = $carrierOptions['name'];
				unset( $carrierOptions['name'] );
				$addition       = [
					'original_name' => $originalName,
					'cart_name'     => $cartName,
				];
				$carrierOptions = array_merge( $addition, $carrierOptions );

				$carrierOptions['count_of_orders'] = 0;
				if ( $carrierId ) {
					$orders = wc_get_orders(
						[
							'packetery_carrier_id' => $carrierId,
							'nopaging'             => true,
						]
					);
					if ( $orders ) {
						$carrierOptions['count_of_orders'] = count( $orders );
					}
				}

				$carriersWithSomeOptions[ $optionId ] = $carrierOptions;
			}
		}

		return $carriersWithSomeOptions;
	}

	/**
	 * Gets array of active carriers names by country code.
	 *
	 * @param string $countryCode Country code.
	 *
	 * @return array
	 */
	private function getActiveCarriersNamesByCountry( string $countryCode ): array {
		$activeCarriers  = [];
		$countryCarriers = $this->carrierEntityRepository->getByCountryIncludingNonFeed( $countryCode );
		foreach ( $countryCarriers as $carrier ) {
			$optionId       = OptionPrefixer::getOptionId( $carrier->getId() );
			$carrierOptions = get_option( $optionId );
			if ( false !== $carrierOptions && $carrierOptions['active'] ) {
				$activeCarriers[] = $carrier->getName();
			}
		}

		return $activeCarriers;
	}
}
