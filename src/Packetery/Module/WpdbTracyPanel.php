<?php
/**
 * Class WpdbTracyPanel
 *
 * @package packetery
 */

declare( strict_types=1 );


namespace Packetery\Module;

use PacketeryLatte;
use PacketeryTracy;

/**
 * Class WpdbTracyPanel
 *
 * @package packetery
 */
class WpdbTracyPanel implements PacketeryTracy\IBarPanel {

	/**
	 * WpdbAdapter.
	 *
	 * @var WpdbAdapter
	 */
	private $wpdbAdapter;

	/**
	 * Latte engine.
	 *
	 * @var PacketeryLatte\Engine
	 */
	private $latteEngine;

	/**
	 * Constructor.
	 *
	 * @param WpdbAdapter           $wpdbAdapter WpdbAdapter.
	 * @param PacketeryLatte\Engine $latteEngine Latte engine.
	 */
	public function __construct(
		WpdbAdapter $wpdbAdapter,
		PacketeryLatte\Engine $latteEngine
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
		$queries = $this->wpdbAdapter->getWpdbQueries();

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
			if ( $count >= 1000 ) {
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
