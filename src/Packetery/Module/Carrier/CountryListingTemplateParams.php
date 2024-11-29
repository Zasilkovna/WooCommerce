<?php
/**
 * Class CountryListingTemplateParams
 *
 * @package Packetery\Carrier
 */

declare( strict_types=1 );

namespace Packetery\Module\Carrier;

/**
 * Class CountryListingTemplateParams
 *
 * @package Packetery\Carrier
 */
class CountryListingTemplateParams {

	/**
	 * Carriers Update params.
	 *
	 * @var array<string, string|array<string, string>>
	 */
	public $carriersUpdate;

	/**
	 * Countries.
	 *
	 * @var array<mixed>
	 */
	public $countries;

	/**
	 * Tells whether API password is set, or not.
	 *
	 * @var bool
	 */
	public $isApiPasswordSet;

	/**
	 * Next update run.
	 *
	 * @var string|null
	 */
	public $nextScheduledRun;

	/**
	 * Settings changed message.
	 *
	 * @var string|null
	 */
	public $settingsChangedMessage;

	/**
	 * Translations.
	 *
	 * @var array<string, string>
	 */
	public $translations;

	/**
	 * @var bool
	 */
	public $isCzechLocale;

	/**
	 * @var string|null
	 */
	public $logoZasilkovna;

	/**
	 * @var string|null
	 */
	public $logoPacketa;

	public function __construct(
		array $carriersUpdate,
		array $countries,
		bool $isApiPasswordSet,
		?string $nextScheduledRun,
		?string $settingsChangedMessage,
		bool $isCzechLocale,
		?string $logoZasilkovna,
		?string $logoPacketa,
		array $translations
	) {
		$this->carriersUpdate         = $carriersUpdate;
		$this->countries              = $countries;
		$this->isApiPasswordSet       = $isApiPasswordSet;
		$this->nextScheduledRun       = $nextScheduledRun;
		$this->settingsChangedMessage = $settingsChangedMessage;
		$this->isCzechLocale          = $isCzechLocale;
		$this->logoZasilkovna         = $logoZasilkovna;
		$this->logoPacketa            = $logoPacketa;
		$this->translations           = $translations;
	}
}
