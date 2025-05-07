<?php

declare( strict_types=1 );

namespace Tests\Module;

use Packetery\Core\Entity\Address;
use Packetery\Core\Entity\Carrier;
use Packetery\Core\Entity\Order;
use Packetery\Core\PickupPointProvider\CompoundCarrierCollectionFactory;
use Packetery\Core\PickupPointProvider\VendorCollectionFactory;
use Packetery\Module\Carrier\OptionPrefixer;
use Packetery\Module\Carrier\PacketaPickupPointsConfig;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\FlagManager\FeatureFlagProvider;
use Packetery\Module\WidgetOptionsBuilder;
use PHPUnit\Framework\TestCase;
use Tests\Core\DummyFactory;

class WidgetOptionsBuilderTest extends TestCase {
	/**
	 * @return array[]
	 */
	public static function pickupPointForAdminDataProvider(): array {
		return [
			[
				'order'              => new Order( 'dummyNumber123', DummyFactory::createCarrierZpointcz() ),
				'expectedCountry'    => 'cz',
				'splitActive'        => true,
				'adultContent'       => true,
				'existingKeys'       => [ 'language', 'appIdentity', 'weight', 'livePickupPoint', 'vendors' ],
				'existingVendorKeys' => [ 'selected', 'country', 'group' ],
				'vendorsSize'        => 2,
				'notExistingKeys'    => [ 'carriers' ],
			],
			[
				'order'              => new Order( 'dummyNumber123', DummyFactory::createCarrierZpointcz() ),
				'expectedCountry'    => 'cz',
				'splitActive'        => true,
				'adultContent'       => false,
				'existingKeys'       => [ 'language', 'appIdentity', 'weight', 'vendors' ],
				'existingVendorKeys' => [ 'selected', 'country', 'group' ],
				'vendorsSize'        => 2,
				'notExistingKeys'    => [ 'carriers', 'livePickupPoint' ],
			],
			[
				'order'              => new Order( 'dummyNumber123', DummyFactory::createCarrierCzechHdRequiresSize() ),
				'expectedCountry'    => 'cz',
				'splitActive'        => true,
				'adultContent'       => false,
				'existingKeys'       => [ 'language', 'appIdentity', 'weight', 'vendors' ],
				'existingVendorKeys' => [ 'selected', 'carrierId' ],
				'vendorsSize'        => 1,
				'notExistingKeys'    => [ 'carriers', 'livePickupPoint' ],
			],
			[
				'order'              => new Order( 'dummyNumber123', DummyFactory::createCarrierGermanHd() ),
				'expectedCountry'    => 'de',
				'splitActive'        => true,
				'adultContent'       => false,
				'existingKeys'       => [ 'language', 'appIdentity', 'weight', 'vendors' ],
				'existingVendorKeys' => [ 'selected', 'carrierId' ],
				'vendorsSize'        => 1,
				'notExistingKeys'    => [ 'carriers', 'livePickupPoint' ],
			],
			[
				'order'              => new Order( 'dummyNumber123', DummyFactory::createCarrierGermanHd() ),
				'expectedCountry'    => 'de',
				'splitActive'        => true,
				'adultContent'       => true,
				'existingKeys'       => [ 'language', 'appIdentity', 'weight', 'vendors', 'livePickupPoint' ],
				'existingVendorKeys' => [ 'selected', 'carrierId' ],
				'vendorsSize'        => 1,
				'notExistingKeys'    => [ 'carriers' ],
			],
			[
				'order'                  => new Order( 'dummyNumber123', DummyFactory::createCarrierZpointcz() ),
				'expectedCountry'        => 'cz',
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
				'expectedCountry'        => 'cz',
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
				'expectedCountry'        => 'de',
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
				'expectedCountry'        => 'cz',
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
		$wpAdapterMock           = $this->createMock( WpAdapter::class );
		$featureFlagProviderMock = $this->createMock( FeatureFlagProvider::class );

		$config = new PacketaPickupPointsConfig(
			new CompoundCarrierCollectionFactory(),
			new VendorCollectionFactory(),
			$featureFlagProviderMock
		);

		$builder = new WidgetOptionsBuilder(
			$config,
			$featureFlagProviderMock,
			$wpAdapterMock
		);

		$featureFlagProviderMock
			->method( 'isSplitActive' )
			->willReturn( $splitActive );

		$order->setShippingCountry( $order->getCarrier()->getCountry() );
		$order->setAdultContent( $adultContent );
		$result = $builder->createPickupPointForAdmin( $order );

		$this->assertSame( $expectedCountry, $result['country'] );
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
			$this->assertSame( 0, $vendorsSize, 'vendors key does not exist so its size must be 0' );
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

	/**
	 * @return array[]
	 */
	public static function addressForAdminDataProvider(): array {
		$alwaysPresentKeys = [ 'country', 'language', 'layout', 'appIdentity', 'street', 'city', 'postcode' ];

		return [
			[
				'order'           => new Order( '123', DummyFactory::createCarrierZpointcz() ),
				'address'         => DummyFactory::createAddress(),
				'expectedKeys'    => $alwaysPresentKeys,
				'nonExistingKeys' => [ 'houseNumber', 'county', 'carrierId' ],
			],
			[
				'order'           => new Order( '123', DummyFactory::createCarrierZpointcz() ),
				'address'         => DummyFactory::createAddress( '123' ),
				'expectedKeys'    => array_merge( $alwaysPresentKeys, [ 'houseNumber' ] ),
				'nonExistingKeys' => [ 'carrierId', 'county' ],
			],
			[
				'order'           => new Order( '123', DummyFactory::createCarrierZpointcz() ),
				'address'         => DummyFactory::createAddress( '123', 'county' ),
				'expectedKeys'    => array_merge( $alwaysPresentKeys, [ 'houseNumber', 'county' ] ),
				'nonExistingKeys' => [ 'carrierId' ],
			],
			[
				'order'           => new Order( '123', DummyFactory::createCarrierCzechHdRequiresSize() ),
				'address'         => DummyFactory::createAddress( '123', 'county' ),
				'expectedKeys'    => array_merge( $alwaysPresentKeys, [ 'houseNumber', 'county', 'carrierId' ] ),
				'nonExistingKeys' => [],
			],
			[
				'order'           => new Order( '123', DummyFactory::createCarrierCzechHdRequiresSize() ),
				'address'         => DummyFactory::createAddress( '123' ),
				'expectedKeys'    => array_merge( $alwaysPresentKeys, [ 'houseNumber', 'carrierId' ] ),
				'nonExistingKeys' => [],
			],
			[
				'order'           => new Order( '123', DummyFactory::createCarrierCzechHdRequiresSize() ),
				'address'         => DummyFactory::createAddress(),
				'expectedKeys'    => array_merge( $alwaysPresentKeys, [ 'carrierId' ] ),
				'nonExistingKeys' => [],
			],
		];
	}

	/**
	 * @dataProvider addressForAdminDataProvider
	 */
	public function testAddressForAdmin(
		Order $order,
		Address $address,
		array $expectedKeys,
		array $nonExistingKeys
	): void {
		$wpAdapterMock           = $this->createMock( WpAdapter::class );
		$featureFlagProviderMock = $this->createMock( FeatureFlagProvider::class );

		$config = new PacketaPickupPointsConfig(
			new CompoundCarrierCollectionFactory(),
			new VendorCollectionFactory(),
			$featureFlagProviderMock
		);

		$builder = new WidgetOptionsBuilder(
			$config,
			$featureFlagProviderMock,
			$wpAdapterMock
		);

		$order->setDeliveryAddress( $address );
		$result = $builder->createAddressForAdmin( $order );

		foreach ( $expectedKeys as $key ) {
			$this->assertArrayHasKey( $key, $result );
		}

		foreach ( $nonExistingKeys as $key ) {
			$this->assertArrayNotHasKey( $key, $result );
		}

		$this->assertSame( 'hd', $result['layout'] );
	}

	/**
	 * @return array[]
	 */
	public static function getCarrierForCheckoutDataProvider(): array {
		$alwaysPresentKeys = [ 'id', 'is_pickup_points' ];

		return [
			[
				'carrier'           => DummyFactory::createCarrierCzechHdRequiresSize(),
				'splitActive'       => true,
				'expectedKeys'      => array_merge( $alwaysPresentKeys, [ 'address_validation' ] ),
				'nonExistingKeys'   => [ 'vendors', 'carriers' ],
				'addressValidation' => 'optional',
			],
			[
				'carrier'           => DummyFactory::createCarrierCzechHdRequiresSize(),
				'splitActive'       => false,
				'expectedKeys'      => array_merge( $alwaysPresentKeys, [ 'address_validation' ] ),
				'nonExistingKeys'   => [ 'vendors', 'carriers' ],
				'addressValidation' => 'optional',
			],
			[
				'carrier'           => DummyFactory::createCarrierGermanHd(),
				'splitActive'       => true,
				'expectedKeys'      => array_merge( $alwaysPresentKeys, [ 'address_validation' ] ),
				'nonExistingKeys'   => [ 'vendors', 'carriers' ],
				'addressValidation' => 'none',
			],
			[
				'carrier'           => DummyFactory::createCarrierGermanHd(),
				'splitActive'       => false,
				'expectedKeys'      => array_merge( $alwaysPresentKeys, [ 'address_validation' ] ),
				'nonExistingKeys'   => [ 'vendors', 'carriers' ],
				'addressValidation' => 'none',
			],
			[
				'carrier'         => DummyFactory::createCarrierZpointcz(),
				'splitActive'     => true,
				'expectedKeys'    => array_merge( $alwaysPresentKeys, [ 'vendors' ] ),
				'nonExistingKeys' => [ 'address_validation', 'carriers' ],
			],
			[
				'carrier'         => DummyFactory::createCarrierZpointcz(),
				'splitActive'     => false,
				'expectedKeys'    => array_merge( $alwaysPresentKeys, [ 'carriers' ] ),
				'nonExistingKeys' => [ 'address_validation', 'vendors' ],
			],
			[
				'carrier'         => DummyFactory::createCarrierCzzpoint(),
				'splitActive'     => true,
				'expectedKeys'    => array_merge( $alwaysPresentKeys, [ 'vendors' ] ),
				'nonExistingKeys' => [ 'address_validation', 'carriers' ],
			],
		];
	}

	/**
	 * @dataProvider getCarrierForCheckoutDataProvider
	 */
	public function testGetCarrierForCheckout(
		Carrier $carrier,
		bool $splitActive,
		array $expectedKeys,
		array $nonExistingKeys,
		?string $addressValidation = null
	): void {
		$wpAdapterMock           = $this->createMock( WpAdapter::class );
		$featureFlagProviderMock = $this->createMock( FeatureFlagProvider::class );

		$config = new PacketaPickupPointsConfig(
			new CompoundCarrierCollectionFactory(),
			new VendorCollectionFactory(),
			$featureFlagProviderMock
		);

		$builder = new WidgetOptionsBuilder(
			$config,
			$featureFlagProviderMock,
			$wpAdapterMock
		);

		$optionId = OptionPrefixer::getOptionId( $carrier->getId() );
		$wpAdapterMock
			->method( 'getOption' )
			->willReturn(
				[
					'address_validation' => $addressValidation,
				]
			);

		$featureFlagProviderMock
			->method( 'isSplitActive' )
			->willReturn( $splitActive );

		$result = $builder->getCarrierForCheckout(
			$carrier,
			$optionId
		);

		foreach ( $expectedKeys as $key ) {
			$this->assertArrayHasKey( $key, $result );
		}

		foreach ( $nonExistingKeys as $key ) {
			$this->assertArrayNotHasKey( $key, $result );
		}

		if ( $addressValidation !== null ) {
			$this->assertContains( 'address_validation', $expectedKeys, 'address_validation key must be present in expectedKeys' );
			$this->assertSame( $addressValidation, $result['address_validation'] );
		}
	}
}
