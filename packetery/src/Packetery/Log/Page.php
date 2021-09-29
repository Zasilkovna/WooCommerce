<?php
/**
 * Class Page
 *
 * @package Packetery\Log
 */

declare( strict_types=1 );


namespace Packetery\Log;

use PacketeryLatte\Engine;

/**
 * Class Page
 *
 * @package Packetery\Log
 */
class Page {

	/**
	 * Engine.
	 *
	 * @var Engine
	 */
	private $latteEngine;

	/**
	 * Manager.
	 *
	 * @var Manager
	 */
	private $manager;

	/**
	 * Page constructor.
	 *
	 * @param Engine  $latteEngine Engine.
	 * @param Manager $manager     Manager.
	 */
	public function __construct( Engine $latteEngine, Manager $manager ) {
		$this->latteEngine = $latteEngine;
		$this->manager     = $manager;
	}

	/**
	 * Registers Page.
	 */
	public function register(): void {
		add_submenu_page(
			'packeta-options',
			__( 'logsPageTitle', 'packetery' ),
			__( 'logsPageTitle', 'packetery' ),
			'manage_options',
			'packeta-logs',
			[ $this, 'render' ]
		);
	}

	/**
	 * Renders Page.
	 */
	public function render(): void {
		$rows = $this->manager->getLogs(
			[
				'orderby' => 'date',
				'order'   => 'DESC',
			]
		);

		$this->latteEngine->render( PACKETERY_PLUGIN_DIR . '/template/log/page.latte', [ 'rows' => $rows ] );
	}
}
