<?php
/**
 * Class PacketReturn.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\EntityFactory;

use Packetery\Core\CoreHelper;
use Packetery\Core\Entity;
use Packetery\Module\Framework\WpAdapter;

/**
 * Class PacketReturn.
 */
class PacketReturn {

	private WpAdapter $wpAdapter;

	public function __construct( WpAdapter $wpAdapter ) {
		$this->wpAdapter = $wpAdapter;
	}

	/**
	 * Creates a return entity from a standardized DB structure.
	 *
	 * @param array<string, mixed> $data Data.
	 *
	 * @return Entity\PacketReturn
	 */
	public function fromStandardizedStructure( array $data ): Entity\PacketReturn {
		$createdAt = \DateTimeImmutable::createFromFormat(
			CoreHelper::MYSQL_DATETIME_FORMAT,
			is_string( $data['created_at'] ) ? $data['created_at'] : '',
			new \DateTimeZone( 'UTC' )
		);
		if ( $createdAt === false ) {
			$createdAt = CoreHelper::now();
		}

		$entity = new Entity\PacketReturn(
			$this->toStringValue( $data['order_id'] ),
			$this->toStringValue( $data['status'] ),
			$this->toStringValue( $data['source'] ),
			$createdAt->setTimezone( $this->wpAdapter->timezone() )
		);
		$entity->setId( $this->toNullableString( $data['id'] ?? null ) );
		$entity->setPacketClaimId( $this->toNullableString( $data['packet_claim_id'] ) );
		$entity->setPacketClaimPassword( $this->toNullableString( $data['packet_claim_password'] ) );
		$entity->setEmail( $this->toNullableString( $data['email'] ) );
		$entity->setPhone( $this->toNullableString( $data['phone'] ) );

		return $entity;
	}

	/**
	 * @param mixed $value Raw DB value.
	 */
	private function toStringValue( $value ): string {
		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * @param mixed $value Raw DB value.
	 */
	private function toNullableString( $value ): ?string {
		return is_scalar( $value ) ? (string) $value : null;
	}
}
