<?php
/**
 * Class CountryListingPage
 *
 * @package Packetery\Carrier
 */

namespace Packetery\Carrier;

use Latte\Engine;

/**
 * Class CountryListingPage
 *
 * @package Packetery\Carrier
 */
class CountryListingPage {

	/**
	 * Latte_engine.
	 *
	 * @var Engine Latte engine.
	 */
	private $latteEngine;

	/**
	 * Carrier repository.
	 *
	 * @var Repository Carrier repository.
	 */
	private $carrierRepository;

	/**
	 * CountryListingPage constructor.
	 *
	 * @param Engine     $latteEngine Latte engine.
	 * @param Repository $carrierRepository Carrier repository.
	 */
	public function __construct( Engine $latteEngine, Repository $carrierRepository ) {
		$this->latteEngine       = $latteEngine;
		$this->carrierRepository = $carrierRepository;
	}

	/**
	 *  Renders page.
	 */
	public function render(): void {
		$countries = $this->getActiveCountries();

		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/carrier/countries.latte',
			[ 'countries' => $countries ]
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
				'url'  => get_admin_url( null, 'admin.php?page=packeta-country&code=' . $country ),
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

}
