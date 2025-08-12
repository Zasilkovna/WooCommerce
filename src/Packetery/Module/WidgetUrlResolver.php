<?php

namespace Packetery\Module;

class WidgetUrlResolver {

	public const WIDGET_URL_PRODUCTION = 'https://widget.packeta.com/v6/www/js/library.js';
	public const WIDGET_URL_STAGE      = 'https://stage.widget.packeta.dev/v6/www/js/library-stage.js';

	/**
	 * @var string
	 */
	private $widgetEnvironment;

	public function __construct( string $widgetEnvironment ) {
		$this->widgetEnvironment = $widgetEnvironment;
	}

	public function getUrl(): string {
		if ( $this->widgetEnvironment === 'stage' ) {
			return self::WIDGET_URL_STAGE;
		}

		return self::WIDGET_URL_PRODUCTION;
	}
}
