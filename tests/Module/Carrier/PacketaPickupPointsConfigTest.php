<?php

declare( strict_types=1 );

namespace Tests\Module\Carrier;

use Packetery\Core\PickupPointProvider\CompoundCarrierCollectionFactory;
use Packetery\Core\PickupPointProvider\VendorCollectionFactory;
use Packetery\Module\Carrier\PacketaPickupPointsConfig;
use PHPUnit\Framework\TestCase;

class PacketaPickupPointsConfigTest extends TestCase {
	public static function compoundCarrierGroupsProvider(): array {
		return [
			'CZ'           => [
				'carrierId'      => 'zpointcz',
				'expectedGroups' => [
					'zpoint',
					'zbox',
				],
			],
			'SK'           => [
				'carrierId'      => 'zpointsk',
				'expectedGroups' => [
					'zpoint',
					'zbox',
				],
			],
			'HU'           => [
				'carrierId'      => 'zpointhu',
				'expectedGroups' => [
					'zpoint',
					'zbox',
				],
			],
			'RO'           => [
				'carrierId'      => 'zpointro',
				'expectedGroups' => [
					'zpoint',
					'zbox',
				],
			],
			'numeric'      => [
				'carrierId'      => '106',
				'expectedGroups' => [],
			],
			'not-existing' => [
				'carrierId'      => 'dummy',
				'expectedGroups' => [],
			],
		];
	}

	/**
	 * @dataProvider compoundCarrierGroupsProvider
	 */
	public function testGetCompoundCarrierVendorGroups( string $carrierId, array $expectedGroups ): void {
		$packetaPickupPointsConfig = new PacketaPickupPointsConfig(
			new CompoundCarrierCollectionFactory(),
			new VendorCollectionFactory()
		);

		self::assertEquals( $expectedGroups, $packetaPickupPointsConfig->getCompoundCarrierVendorGroups( $carrierId ) );
	}
}
