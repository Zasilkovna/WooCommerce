<?php

declare( strict_types=1 );

namespace Tests\Module;

use Packetery\Core\Entity\Order;
use Packetery\Core\PickupPointProvider\CompoundCarrierCollectionFactory;
use Packetery\Core\PickupPointProvider\VendorCollectionFactory;
use Packetery\Module\Carrier\PacketaPickupPointsConfig;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\FlagManager\FeatureFlagProvider;
use Packetery\Module\WidgetOptionsBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tests\Core\DummyFactory;

class WidgetOptionsBuilderTest extends TestCase {

	/**
	 * @var FeatureFlagProvider|MockObject
	 */
	private $featureFLagProviderMock;

	/**
	 * @var WidgetOptionsBuilder
	 */
	private $builder;

	protected function setUp(): void {
		$wpAdapterMock                 = $this->createMock( WpAdapter::class );
		$this->featureFLagProviderMock = $this->createMock( FeatureFlagProvider::class );

		$config = new PacketaPickupPointsConfig(
			new CompoundCarrierCollectionFactory(),
			new VendorCollectionFactory(),
			$this->featureFLagProviderMock
		);

		$this->builder = new WidgetOptionsBuilder(
			$config,
			$this->featureFLagProviderMock,
			$wpAdapterMock
		);
	}

	/**
	 * @return array[]
	 */
	public static function pickupPointForAdminDataProvider(): array {
		return [
			[
				'order'              => new Order( 'dummyNumber123', DummyFactory::createCarrierZpointcz() ),
				'country'            => 'cz',
				'splitActive'        => true,
				'adultContent'       => true,
				'existingKeys'       => [ 'language', 'appIdentity', 'weight', 'livePickupPoint', 'vendors' ],
				'existingVendorKeys' => [ 'selected', 'country', 'group' ],
				'vendorsSize'        => 2,
				'notExistingKeys'    => [ 'carriers' ],
			],
			[
				'order'              => new Order( 'dummyNumber123', DummyFactory::createCarrierZpointcz() ),
				'country'            => 'cz',
				'splitActive'        => true,
				'adultContent'       => false,
				'existingKeys'       => [ 'language', 'appIdentity', 'weight', 'vendors' ],
				'existingVendorKeys' => [ 'selected', 'country', 'group' ],
				'vendorsSize'        => 2,
				'notExistingKeys'    => [ 'carriers', 'livePickupPoint' ],
			],
			[
				'order'              => new Order( 'dummyNumber123', DummyFactory::createCarrierCzechHdRequiresSize() ),
				'country'            => 'cz',
				'splitActive'        => true,
				'adultContent'       => false,
				'existingKeys'       => [ 'language', 'appIdentity', 'weight', 'vendors' ],
				'existingVendorKeys' => [ 'selected', 'carrierId' ],
				'vendorsSize'        => 1,
				'notExistingKeys'    => [ 'carriers', 'livePickupPoint' ],
			],
			[
				'order'              => new Order( 'dummyNumber123', DummyFactory::createCarrierGermanHd() ),
				'country'            => 'de',
				'splitActive'        => true,
				'adultContent'       => false,
				'existingKeys'       => [ 'language', 'appIdentity', 'weight', 'vendors' ],
				'existingVendorKeys' => [ 'selected', 'carrierId' ],
				'vendorsSize'        => 1,
				'notExistingKeys'    => [ 'carriers', 'livePickupPoint' ],
			],
			[
				'order'              => new Order( 'dummyNumber123', DummyFactory::createCarrierGermanHd() ),
				'country'            => 'de',
				'splitActive'        => true,
				'adultContent'       => true,
				'existingKeys'       => [ 'language', 'appIdentity', 'weight', 'vendors', 'livePickupPoint' ],
				'existingVendorKeys' => [ 'selected', 'carrierId' ],
				'vendorsSize'        => 1,
				'notExistingKeys'    => [ 'carriers' ],
			],
			[
				'order'                  => new Order( 'dummyNumber123', DummyFactory::createCarrierZpointcz() ),
				'country'                => 'cz',
				'splitActive'            => false,
				'adultContent'           => true,
				'existingKeys'           => [ 'language', 'appIdentity', 'weight', 'carriers', 'livePickupPoint' ],
				'existingVendorKeys'     => [],
				'vendorsSize'            => 0,
				'notExistingKeys'        => [ 'vendors' ],
				'carriersValueValidator' => 'is_string',
			],
			[
				'order'                  => new Order( 'dummyNumber123', DummyFactory::createCarrierZpointcz() ),
				'country'                => 'cz',
				'splitActive'            => false,
				'adultContent'           => false,
				'existingKeys'           => [ 'language', 'appIdentity', 'weight', 'carriers' ],
				'existingVendorKeys'     => [],
				'vendorsSize'            => 0,
				'notExistingKeys'        => [ 'vendors', 'livePickupPoint' ],
				'carriersValueValidator' => 'is_string',
			],
			[
				'order'                  => new Order( 'dummyNumber123', DummyFactory::createCarrierGermanPp() ),
				'country'                => 'de',
				'splitActive'            => false,
				'adultContent'           => false,
				'existingKeys'           => [ 'language', 'appIdentity', 'weight', 'carriers' ],
				'existingVendorKeys'     => [],
				'vendorsSize'            => 0,
				'notExistingKeys'        => [ 'vendors', 'livePickupPoint' ],
				'carriersValueValidator' => 'is_numeric',
			],
			[
				'order'                  => new Order( 'dummyNumber123', DummyFactory::createCarrierCzechHdRequiresSize() ),
				'country'                => 'cz',
				'splitActive'            => false,
				'adultContent'           => false,
				'existingKeys'           => [ 'language', 'appIdentity', 'weight', 'carriers' ],
				'existingVendorKeys'     => [],
				'vendorsSize'            => 0,
				'notExistingKeys'        => [ 'vendors', 'livePickupPoint' ],
				'carriersValueValidator' => 'is_null',
			],
		];
	}

	/**
	 * @dataProvider pickupPointForAdminDataProvider
	 */
	public function testPickupPointForAdmin(
		Order $order,
		string $expectedCountry,
		bool $splitActive,
		bool $adultContent,
		array $existingKeys,
		array $existingVendorKeys,
		int $vendorsSize,
		array $notExistingKeys,
		?string $carriersValueValidator = null
	): void {
		$this->featureFLagProviderMock
			->method( 'isSplitActive' )
			->willReturn( $splitActive );

		$order->setShippingCountry( $order->getCarrier()->getCountry() );
		$order->setAdultContent( $adultContent );
		$result = $this->builder->createPickupPointForAdmin( $order );

		$this->assertEquals( $expectedCountry, $result['country'] );
		foreach ( $existingKeys as $key ) {
			$this->assertArrayHasKey( $key, $result );
		}

		foreach ( $notExistingKeys as $key ) {
			$this->assertArrayNotHasKey( $key, $result );
		}

		if ( in_array( 'vendors', $existingKeys, true ) ) {
			$this->assertCount( $vendorsSize, $result['vendors'] );
			foreach ( $result['vendors'] as $vendor ) {
				foreach ( $vendor as $key => $value ) {
					$this->assertContains( $key, $existingVendorKeys );
				}
			}
		} else {
			$this->assertEquals( 0, $vendorsSize, 'vendors key does not exist so its size must be 0' );
			$this->assertEmpty( $existingVendorKeys, 'vendors key does not exist so existing keys array must be empty' );
		}

		if ( $carriersValueValidator !== null ) {
			$this->assertTrue(
				call_user_func( $carriersValueValidator, $result['carriers'] ),
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
				'carriers value "' . var_export( $result['carriers'], true ) . '" do not validate with ' . $carriersValueValidator
			);
		}
	}
}
