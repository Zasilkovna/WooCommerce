<?php
/**
 * Class CountryListingTemplateParams
 *
 * @package Packetery\Carrier
 */

declare( strict_types=1 );

namespace Packetery\Module\Carrier;

use Packetery\Nette\Forms\Form;

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
	 * Tells if countries contain any carriers.
	 *
	 * @var bool
	 */
	public $hasCarriers;

	/**
	 * Form.
	 *
	 * @var Form
	 */
	public $form;

	/**
	 * CountryListingTemplateParams constructor.
	 *
	 * @param array       $carriersUpdate         Carriers update params.
	 * @param array       $countries              Countries.
	 * @param bool        $isApiPasswordSet       Tells whether API password is set, or not.
	 * @param string|null $nextScheduledRun       Next update run.
	 * @param string|null $settingsChangedMessage Settings changed message.
	 * @param array       $translations           Translations.
	 * @param bool        $hasCarriers            Tells if any country has carriers.
	 * @param Form        $form                   Filter form.
	 */
	public function __construct(
		array $carriersUpdate,
		array $countries,
		bool $isApiPasswordSet,
		?string $nextScheduledRun,
		?string $settingsChangedMessage,
		array $translations,
		bool $hasCarriers,
		Form $form
	) {
		$this->carriersUpdate         = $carriersUpdate;
		$this->countries              = $countries;
		$this->isApiPasswordSet       = $isApiPasswordSet;
		$this->nextScheduledRun       = $nextScheduledRun;
		$this->settingsChangedMessage = $settingsChangedMessage;
		$this->translations           = $translations;
		$this->hasCarriers            = $hasCarriers;
		$this->form                   = $form;
	}
}
