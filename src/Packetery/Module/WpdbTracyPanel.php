<?php
/**
 * Class WpdbTracyPanel
 *
 * @package packetery
 */

declare( strict_types=1 );


namespace Packetery\Module;

use Packetery\Latte;
use Packetery\Tracy;

/**
 * Class WpdbTracyPanel
 *
 * @package packetery
 */
class WpdbTracyPanel implements Tracy\IBarPanel {

	/**
	 * WpdbAdapter.
	 *
	 * @var WpdbAdapter
	 */
	private $wpdbAdapter;

	/**
	 * Latte engine.
	 *
	 * @var Latte\Engine
	 */
	private $latteEngine;

	/**
	 * Constructor.
	 *
	 * @param WpdbAdapter  $wpdbAdapter WpdbAdapter.
	 * @param Latte\Engine $latteEngine Latte engine.
	 */
	public function __construct(
		WpdbAdapter $wpdbAdapter,
		Latte\Engine $latteEngine
	) {
		$this->wpdbAdapter = $wpdbAdapter;
		$this->latteEngine = $latteEngine;
	}

	/**
	 * Get queries.
	 *
	 * @return \Generator
	 */
	private function getPacketeryQueries(): \Generator {
		$queries    = $this->wpdbAdapter->getWpdbQueries();
		$maxQueries = defined( 'PACKETERY_DEBUG_MAX_DB_PANEL_QUERIES' ) ? PACKETERY_DEBUG_MAX_DB_PANEL_QUERIES : 1000;

		$count = 0;
		foreach ( $queries as $queryInfo ) {
			[ $query, $timeSpent, $funcList ] = $queryInfo;

			if ( false === strpos( $funcList, 'Packetery\\' ) && false === strpos( $funcList, '/plugins/packeta/' ) ) {
				continue;
			}

			yield [
				'query'     => $query,
				'timeSpent' => $timeSpent * 1000,
				'funcList'  => $funcList,
			];

			$count++;
			if ( $count >= $maxQueries ) {
				yield false;
				break;
			}
		}
	}

	/**
	 * Gets HTML code for custom tab.
	 *
	 * @return string
	 */
	public function getTab(): string {
		return $this->latteEngine->renderToString( PACKETERY_PLUGIN_DIR . '/template/wpdb-tracy-panel-tab.latte' );
	}

	/**
	 * Gets HTML code for custom panel.
	 *
	 * @return string
	 */
	public function getPanel(): string {
		return $this->latteEngine->renderToString(
			PACKETERY_PLUGIN_DIR . '/template/wpdb-tracy-panel.latte',
			[
				'queries' => $this->getPacketeryQueries(),
			]
		);
	}
}
