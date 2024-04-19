<?php
/**
 * Class SurveyConfig
 *
 * @package Packetery
 */

namespace Packetery\Module;

/**
 * Class SurveyConfig
 *
 * @package Packetery
 */
class SurveyConfig {

	/**
	 * Tells whether to display.
	 *
	 * @var bool
	 */
	public $active;

	/**
	 * Survey URL.
	 *
	 * @var string
	 */
	public $url;

	/**
	 * Survey visual.
	 *
	 * @var string
	 */
	public $image;

	/**
	 * SurveyConfig constructor.
	 *
	 * @param bool   $active Active.
	 * @param string $url    Url.
	 * @param string $image  Image.
	 */
	public function __construct( bool $active, string $url, string $image ) {
		$this->active = $active;
		$this->url    = $url;
		$this->image  = $image;
	}

}
