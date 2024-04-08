<?php
/**
 * Class OptionsPage
 *
 * @package Packetery\Options
 */

declare( strict_types=1 );

namespace Packetery\Module\Carrier;

use Packetery\Core\Entity\Carrier;
use Packetery\Core\Helper;
use Packetery\Core\Rounder;
use Packetery\Module\CarDeliveryConfig;
use Packetery\Module\FormFactory;
use Packetery\Module\FormValidators;
use Packetery\Module\MessageManager;
use Packetery\Module\Options\FeatureFlagManager;
use Packetery\Latte\Engine;
use Packetery\Module\PaymentGatewayHelper;
use Packetery\Nette\Forms\Container;
use Packetery\Nette\Forms\Form;
use Packetery\Nette\Http\Request;

/**
 * Class OptionsPage
 *
 * @package Packetery\Options
 */
class OptionsPage {

	public const SLUG                   = 'packeta-country';
	public const PARAMETER_COUNTRY_CODE = 'country_code';

	/**
	 * PacketeryLatte_engine.
	 *
	 * @var Engine PacketeryLatte engine.
	 */
	private $latteEngine;

	/**
	 * Carrier repository.
	 *
	 * @var EntityRepository Carrier repository.
	 */
	private $carrierRepository;

	/**
	 * Packetery\Nette Request.
	 *
	 * @var Request Packetery\Nette Request.
	 */
	private $httpRequest;

	/**
	 * CountryListingPage.
	 *
	 * @var CountryListingPage CountryListingPage.
	 */
	private $countryListingPage;

	/**
	 * Detail page common logic.
	 *
	 * @var DetailPageCommonLogic
	 */
	private $commonLogic;

	/**
	 * OptionsPage constructor.
	 *
	 * @param Engine                $latteEngine        PacketeryLatte_engine.
	 * @param EntityRepository      $carrierRepository  Carrier repository.
	 * @param Request               $httpRequest        Packetery\Nette Request.
	 * @param CountryListingPage    $countryListingPage CountryListingPage.
	 * @param DetailPageCommonLogic $commonLogic        Common logic.
	 */
	public function __construct(
		Engine $latteEngine,
		EntityRepository $carrierRepository,
		Request $httpRequest,
		CountryListingPage $countryListingPage,
		DetailPageCommonLogic $commonLogic
	) {
		$this->latteEngine        = $latteEngine;
		$this->carrierRepository  = $carrierRepository;
		$this->httpRequest        = $httpRequest;
		$this->countryListingPage = $countryListingPage;
		$this->commonLogic        = $commonLogic;
	}

	/**
	 * Registers WP callbacks.
	 */
	public function register(): void {
		add_submenu_page(
			\Packetery\Module\Options\Page::SLUG,
			__( 'Carrier settings', 'packeta' ),
			__( 'Carrier settings', 'packeta' ),
			'manage_options',
			self::SLUG,
			array(
				$this,
				'render',
			),
			10
		);
	}

	/**
	 *  Renders page.
	 */
	public function render(): void {
		$countryIso = $this->httpRequest->getQuery( self::PARAMETER_COUNTRY_CODE );
		if ( ! $countryIso ) {
			$this->countryListingPage->render();
			return;
		}

		$countryCarriers = $this->carrierRepository->getByCountryIncludingNonFeed( $countryIso );
		$carriersData    = [];
		foreach ( $countryCarriers as $carrier ) {
			$carrierTemplateData = $this->commonLogic->getCarrierTemplateData( $carrier );
			if ( null !== $carrierTemplateData ) {
				$carriersData[] = $carrierTemplateData;
			}
		}

		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/carrier/country.latte',
			array_merge_recursive(
				$this->commonLogic->getBaseRenderTemplateParameters(),
				[
					'forms'        => $carriersData,
					'country_iso'  => $countryIso,
					'translations' => [
						// translators: %s is country code.
						'title' => sprintf( __( 'Country options: %s', 'packeta' ), strtoupper( $countryIso ) ),

					],
				]
			)
		);
	}

	/**
	 * Creates link to page.
	 *
	 * @param string|null $countryCode Country code.
	 *
	 * @return string
	 */
	public function createUrl( ?string $countryCode = null ): string {
		$params = [
			'page' => self::SLUG,
		];

		if ( null !== $countryCode ) {
			$params[ self::PARAMETER_COUNTRY_CODE ] = $countryCode;
		}

		return add_query_arg(
			$params,
			admin_url( 'admin.php' )
		);
	}

}
