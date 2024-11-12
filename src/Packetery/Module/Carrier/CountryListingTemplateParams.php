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
	 * @var array
	 */
	public $carriersUpdate;

	/**
	 * Countries.
	 *
	 * @var array
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
	 * @var array
	 */
	public $translations;

	/**
	 * @var bool
	 */
	private $isCzechLocale;

	public function __construct(
		array $carriersUpdate,
		array $countries,
		bool $isApiPasswordSet,
		?string $nextScheduledRun,
		?string $settingsChangedMessage,
		bool $isCzechLocale,
		array $translations
	) {
		$this->carriersUpdate         = $carriersUpdate;
		$this->countries              = $countries;
		$this->isApiPasswordSet       = $isApiPasswordSet;
		$this->nextScheduledRun       = $nextScheduledRun;
		$this->settingsChangedMessage = $settingsChangedMessage;
		$this->isCzechLocale          = $isCzechLocale;
		$this->translations           = $translations;
	}
}
