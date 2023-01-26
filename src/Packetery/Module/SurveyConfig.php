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
 * @property bool $active
 * @property string $url
 * @property string $image
 * @package Packetery
 */
class SurveyConfig {

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
