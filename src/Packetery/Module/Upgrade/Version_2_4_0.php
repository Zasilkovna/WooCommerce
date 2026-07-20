<?php
/**
 * Class Version_2_4_0
 *
 * @package Packetery\Upgrade
 */

declare( strict_types=1 );

namespace Packetery\Module\Upgrade;

use Packetery\Core\CoreHelper;
use Packetery\Core\Entity\PacketReturn;
use Packetery\Module\WpdbAdapter;

/**
 * Class Version_2_4_0
 *
 * @package Packetery\Upgrade
 */
class Version_2_4_0 {

	private WpdbAdapter $wpdbAdapter;

	/**
	 * Version_2_4_0 constructor.
	 *
	 * @param WpdbAdapter $wpdbAdapter WPDB.
	 */
	public function __construct( WpdbAdapter $wpdbAdapter ) {
		$this->wpdbAdapter = $wpdbAdapter;
	}

	/**
	 * Backfills existing scalar claims from packetery_order into packetery_return.
	 *
	 * Single set-based idempotent INSERT ... SELECT with an anti-join: crash-safe
	 * because Upgrade::check() bumps the stored version only at the very end, so an
	 * interrupted request re-runs the whole block and the anti-join skips already
	 * migrated rows. Backfilled rows use the migration time as created_at (the
	 * original claim date is unknown).
	 *
	 * @return void
	 */
	public function run(): void {
		$sql = sprintf(
			'INSERT INTO `%s` (order_id, status, source, packet_claim_id, packet_claim_password, created_at)
				SELECT o.id, %%s, %%s, o.packet_claim_id, o.packet_claim_password, %%s
				FROM `%s` o
				LEFT JOIN `%s` r ON r.order_id = o.id
				WHERE o.packet_claim_id IS NOT NULL AND r.order_id IS NULL',
			$this->wpdbAdapter->packeteryReturn,
			$this->wpdbAdapter->packeteryOrder,
			$this->wpdbAdapter->packeteryReturn
		);

		$this->wpdbAdapter->query(
			$this->wpdbAdapter->prepare(
				$sql,
				PacketReturn::STATUS_CREATED,
				PacketReturn::SOURCE_ADMIN,
				CoreHelper::now()->format( CoreHelper::MYSQL_DATETIME_FORMAT )
			)
		);
	}
}
