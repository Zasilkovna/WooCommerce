<?php
/**
 * Class CountryListingPage
 *
 * @package Packetery\Carrier
 */

declare( strict_types=1 );

namespace Packetery\Module\Carrier;

use Packetery\Module\Checkout;
use PacketeryLatte\Engine;
use PacketeryNette\Http\Request;

/**
 * Class CountryListingPage
 *
 * @package Packetery\Carrier
 */
class CountryListingPage {

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
	 * CountryListingPage constructor.
	 *
	 * @param Engine     $latteEngine PacketeryLatte engine.
	 * @param Repository $carrierRepository Carrier repository.
	 * @param Downloader $downloader Carrier downloader.
	 * @param Request    $httpRequest Http request.
	 */
	public function __construct( Engine $latteEngine, Repository $carrierRepository, Downloader $downloader, Request $httpRequest ) {
		$this->latteEngine       = $latteEngine;
		$this->carrierRepository = $carrierRepository;
		$this->downloader        = $downloader;
		$this->httpRequest       = $httpRequest;
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

		$countries = $this->getActiveCountries();
		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/carrier/countries.latte',
			[
				'carriersUpdate' => $carriersUpdateParams,
				'countries'      => $countries,
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

		$internalCountries = array_keys( $this->carrierRepository->getZpointCarriers() );
		$countries         = array_unique( array_merge( $internalCountries, $countries ) );

		$countriesFinal = [];
		foreach ( $countries as $country ) {
			$countriesFinal[] = [
				'code' => $country,
				'name' => \Locale::getDisplayRegion( '-' . $country, get_locale() ),
				'url'  => add_query_arg(
					[
						'page' => OptionsPage::SLUG,
						'code' => $country,
					],
					get_admin_url( null, 'admin.php' )
				),
			];
		}

		usort(
			$countriesFinal,
			static function ( $a, $b ) {
				if ( 'cz' === $a['code'] ) {
					return - 1;
				}
				if ( 'sk' === $a['code'] ) {
					if ( 'cz' === $b['code'] ) {
						return 1;
					}

					return - 1;
				}
				if ( 'cz' === $b['code'] ) {
					return 1;
				}
				if ( 'sk' === $b['code'] ) {
					return 1;
				}

				return strnatcmp( $a['name'], $b['name'] );
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
		$activeCarriers = [];
		$allCarriers    = $this->carrierRepository->getAllIncludingZpoints();
		foreach ( $allCarriers as $carrier ) {
			$optionId       = Checkout::CARRIER_PREFIX . $carrier['id'];
			$carrierOptions = get_option( $optionId );
			if ( false !== $carrierOptions ) {
				unset( $carrierOptions['id'] );
				$originalName = $carrier['name'];
				$cartName     = $carrierOptions['name'];
				unset( $carrierOptions['name'] );
				$addition       = [
					'original_name' => $originalName,
					'cart_name'     => $cartName,
				];
				$carrierOptions = array_merge( $addition, $carrierOptions );

				$activeCarriers[ $optionId ] = $carrierOptions;
			}
		}

		return $activeCarriers;
	}

}
