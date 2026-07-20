<?php
/**
 * Class Repository.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Returns;

use Packetery\Core\CoreHelper;
use Packetery\Core\Entity\PacketReturn;
use Packetery\Module\EntityFactory;
use Packetery\Module\Exception\DeleteErrorException;
use Packetery\Module\WpdbAdapter;

/**
 * Class Repository.
 */
class Repository {

	private WpdbAdapter $wpdbAdapter;
	private EntityFactory\PacketReturn $entityFactory;

	public function __construct( WpdbAdapter $wpdbAdapter, EntityFactory\PacketReturn $entityFactory ) {
		$this->wpdbAdapter   = $wpdbAdapter;
		$this->entityFactory = $entityFactory;
	}

	public function getById( string $id ): ?PacketReturn {
		$row = $this->wpdbAdapter->get_row(
			$this->wpdbAdapter->prepare(
				'SELECT * FROM `' . $this->wpdbAdapter->packeteryReturn . '` WHERE `id` = %d',
				$id
			),
			ARRAY_A
		);

		if ( ! is_array( $row ) ) {
			return null;
		}

		return $this->entityFactory->fromStandardizedStructure( $row );
	}

	/**
	 * Gets all returns of an order, newest first.
	 *
	 * @param string $orderId WC order ID.
	 *
	 * @return PacketReturn[]
	 */
	public function getByOrderId( string $orderId ): array {
		$rows = $this->wpdbAdapter->get_results(
			$this->wpdbAdapter->prepare(
				'SELECT * FROM `' . $this->wpdbAdapter->packeteryReturn . '` WHERE `order_id` = %d ORDER BY `id` DESC',
				$orderId
			),
			ARRAY_A
		);

		if ( $rows === null ) {
			return [];
		}

		$returns = [];
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$returns[] = $this->entityFactory->fromStandardizedStructure( $row );
		}

		return $returns;
	}

	/**
	 * Saves a return (insert when new, update otherwise).
	 *
	 * @param PacketReturn $packetReturn Return.
	 *
	 * @return int|false The number of rows affected, or false on error.
	 */
	public function save( PacketReturn $packetReturn ) {
		if ( $packetReturn->getId() === null ) {
			$updatedRowCount = $this->wpdbAdapter->insertReplaceHelper(
				$this->wpdbAdapter->packeteryReturn,
				$this->returnToDbArray( $packetReturn )
			);
			if ( $updatedRowCount !== false ) {
				$packetReturn->setId( $this->wpdbAdapter->getLastInsertId() );
			}

			return $updatedRowCount;
		}

		return $this->wpdbAdapter->update(
			$this->wpdbAdapter->packeteryReturn,
			$this->returnToDbArray( $packetReturn ),
			[ 'id' => (int) $packetReturn->getId() ]
		);
	}

	/**
	 * @throws DeleteErrorException On delete failure.
	 */
	public function delete( string $id ): void {
		$this->wpdbAdapter->delete( $this->wpdbAdapter->packeteryReturn, [ 'id' => (int) $id ], '%d' );
	}

	/**
	 * @param PacketReturn $packetReturn Return.
	 *
	 * @return array<string, string|int|null>
	 */
	private function returnToDbArray( PacketReturn $packetReturn ): array {
		return [
			'id'                    => $packetReturn->getId(),
			'order_id'              => $packetReturn->getOrderId(),
			'status'                => $packetReturn->getStatus(),
			'source'                => $packetReturn->getSource(),
			'packet_claim_id'       => $packetReturn->getPacketClaimId(),
			'packet_claim_password' => $packetReturn->getPacketClaimPassword(),
			'email'                 => $packetReturn->getEmail(),
			'phone'                 => $packetReturn->getPhone(),
			'created_at'            => $packetReturn->getCreatedAt()
				->setTimezone( new \DateTimeZone( 'UTC' ) )
				->format( CoreHelper::MYSQL_DATETIME_FORMAT ),
		];
	}

	public function createOrAlterTable(): bool {
		$createTableQuery = sprintf(
			'CREATE TABLE `%s` (
				`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
				`order_id` bigint(20) UNSIGNED NOT NULL,
				`status` varchar(255) NOT NULL,
				`source` varchar(255) NOT NULL,
				`packet_claim_id` varchar(15) NULL DEFAULT NULL,
				`packet_claim_password` varchar(10) NULL DEFAULT NULL,
				`email` varchar(255) NULL DEFAULT NULL,
				`phone` varchar(64) NULL DEFAULT NULL,
				`created_at` datetime NOT NULL,
			PRIMARY KEY (`id`)
		) %s',
			$this->wpdbAdapter->packeteryReturn,
			$this->wpdbAdapter->get_charset_collate()
		);

		return $this->wpdbAdapter->dbDelta( $createTableQuery, $this->wpdbAdapter->packeteryReturn );
	}
}
