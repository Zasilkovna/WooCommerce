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
	 * CountryListingTemplateParams constructor.
	 *
	 * @param array       $carriersUpdate         Carriers update params.
	 * @param array       $countries              Countries.
	 * @param bool        $isApiPasswordSet       Tells whether API password is set, or not.
	 * @param string|null $nextScheduledRun       Next update run.
	 * @param string|null $settingsChangedMessage Settings changed message.
	 * @param array       $translations           Translations.
	 */
	public function __construct(
		array $carriersUpdate,
		array $countries,
		bool $isApiPasswordSet,
		?string $nextScheduledRun,
		?string $settingsChangedMessage,
		array $translations
	) {
		$this->carriersUpdate         = $carriersUpdate;
		$this->countries              = $countries;
		$this->isApiPasswordSet       = $isApiPasswordSet;
		$this->nextScheduledRun       = $nextScheduledRun;
		$this->settingsChangedMessage = $settingsChangedMessage;
		$this->translations           = $translations;
	}
}
