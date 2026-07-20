<?php

declare( strict_types=1 );

namespace Tests\Packetery\Module\EntityFactory;

use Packetery\Core\Entity\PacketReturn as PacketReturnEntity;
use Packetery\Module\EntityFactory\PacketReturn;
use Packetery\Module\Framework\WpAdapter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PacketReturnFactoryTest extends TestCase {

	private WpAdapter|MockObject $wpAdapter;

	private function createFactory(): PacketReturn {
		$this->wpAdapter = $this->createMock( WpAdapter::class );
		$this->wpAdapter->method( 'timezone' )->willReturn( new \DateTimeZone( 'UTC' ) );

		return new PacketReturn( $this->wpAdapter );
	}

	public function testCreatesFullyPopulatedEntity(): void {
		$entity = $this->createFactory()->fromStandardizedStructure(
			[
				'id'                    => '42',
				'order_id'              => '1001',
				'status'                => PacketReturnEntity::STATUS_CREATED,
				'source'                => PacketReturnEntity::SOURCE_ADMIN,
				'packet_claim_id'       => '9000000123',
				'packet_claim_password' => 'secret',
				'email'                 => 'buyer@example.com',
				'phone'                 => '+420777123456',
				'created_at'            => '2026-07-20 10:30:00',
			]
		);

		self::assertSame( '42', $entity->getId() );
		self::assertSame( '1001', $entity->getOrderId() );
		self::assertSame( PacketReturnEntity::STATUS_CREATED, $entity->getStatus() );
		self::assertSame( PacketReturnEntity::SOURCE_ADMIN, $entity->getSource() );
		self::assertSame( '9000000123', $entity->getPacketClaimId() );
		self::assertSame( 'secret', $entity->getPacketClaimPassword() );
		self::assertSame( 'buyer@example.com', $entity->getEmail() );
		self::assertSame( '+420777123456', $entity->getPhone() );
		self::assertSame( '2026-07-20 10:30:00', $entity->getCreatedAt()->format( 'Y-m-d H:i:s' ) );
	}

	public function testNullableColumnsBecomeNull(): void {
		$entity = $this->createFactory()->fromStandardizedStructure(
			[
				'id'                    => '7',
				'order_id'              => '2002',
				'status'                => PacketReturnEntity::STATUS_PENDING,
				'source'                => PacketReturnEntity::SOURCE_CUSTOMER,
				'packet_claim_id'       => null,
				'packet_claim_password' => null,
				'email'                 => null,
				'phone'                 => null,
				'created_at'            => '2026-01-01 00:00:00',
			]
		);

		self::assertNull( $entity->getPacketClaimId() );
		self::assertNull( $entity->getPacketClaimPassword() );
		self::assertNull( $entity->getEmail() );
		self::assertNull( $entity->getPhone() );
	}

	public function testInvalidCreatedAtFallsBackToNow(): void {
		$entity = $this->createFactory()->fromStandardizedStructure(
			[
				'id'                    => '1',
				'order_id'              => '3003',
				'status'                => PacketReturnEntity::STATUS_CREATED,
				'source'                => PacketReturnEntity::SOURCE_ADMIN,
				'packet_claim_id'       => null,
				'packet_claim_password' => null,
				'email'                 => null,
				'phone'                 => null,
				'created_at'            => 'not-a-date',
			]
		);

		self::assertInstanceOf( \DateTimeImmutable::class, $entity->getCreatedAt() );
	}

	public function testNonStringScalarsAreCoerced(): void {
		$entity = $this->createFactory()->fromStandardizedStructure(
			[
				'id'                    => 5,
				'order_id'              => 4004,
				'status'                => PacketReturnEntity::STATUS_CREATED,
				'source'                => PacketReturnEntity::SOURCE_ADMIN,
				'packet_claim_id'       => null,
				'packet_claim_password' => null,
				'email'                 => null,
				'phone'                 => null,
				'created_at'            => '2026-07-20 10:30:00',
			]
		);

		self::assertSame( '5', $entity->getId() );
		self::assertSame( '4004', $entity->getOrderId() );
	}
}
