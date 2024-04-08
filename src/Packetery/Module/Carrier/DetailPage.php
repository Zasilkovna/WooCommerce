<?php
/**
 * Class DetailPage
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Carrier;

use Packetery\Latte\Engine;
use Packetery\Module\Options;
use Packetery\Module\Plugin;
use Packetery\Nette\Http\Request;

/**
 * Class DetailPage
 *
 * @package Packetery
 */
class DetailPage {

	public const SLUG                 = 'packeta-carrier-detail';
	public const PARAMETER_CARRIER_ID = 'carrier_id';

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
	 * Common logic.
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
			Options\Page::SLUG,
			__( 'Carrier settings', 'packeta' ),
			__( 'Carrier settings', 'packeta' ),
			'manage_options',
			self::SLUG,
			[
				$this,
				'render',
			],
			10
		);
		add_action(
			'admin_head',
			static function (): void {
				Plugin::hideSubmenuItem( self::SLUG );
			}
		);
	}

	/**
	 *  Renders page.
	 */
	public function render(): void {
		$carrierId = $this->httpRequest->getQuery( self::PARAMETER_CARRIER_ID );
		$carrier   = $this->carrierRepository->getAnyById( $carrierId );

		$carrierTemplateData = $this->commonLogic->getCarrierTemplateData( $carrier );
		if ( null === $carrierTemplateData ) {
			$this->countryListingPage->render();
			return;
		}

		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/carrier/detail.latte',
			array_merge_recursive(
				$this->commonLogic->getBaseRenderTemplateParameters(),
				[
					'carrierTemplateData' => $carrierTemplateData,
					'translations'        => [
						'title' => $carrier->getName(),
					],
				]
			)
		);
	}

}
