<?php

declare( strict_types=1 );

namespace Tests\Integration\Module\Checkout;

use Packetery\Module\Checkout\CheckoutStorage;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Order\Attribute as Attr;
use Packetery\Nette\Http\Request;
use Packetery\Nette\Http\UrlScript;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tests\Integration\IntegrationTestsHelper;

class CheckoutStorageTest extends TestCase {
	/**
	 * Allows setting raw POST payload (including non-array) to exercise branches when POST is not an array.
	 *
	 * @param Request $request
	 * @param mixed   $post
	 */
	private static function setRequestRawPost( Request $request, mixed $post ): void {
		$reflection = new ReflectionClass( $request );
		$property   = $reflection->getProperty( 'post' );
		$property->setAccessible( true );
		$property->setValue( $request, $post );
	}

	private static function buildRequest( array $post = [] ): Request {
		return new Request( new UrlScript( '/', '/' ), $post );
	}

	/**
	 * Builds request and sets raw POST value (can be scalar or object).
	 *
	 * @param mixed $post
	 *
	 * @return Request
	 */
	private static function buildRequestWithRawPost( mixed $post ): Request {
		$request = new Request( new UrlScript( '/', '/' ) );
		self::setRequestRawPost( $request, $post );

		return $request;
	}

	private function invokeTransientName( CheckoutStorage $storage ): string {
		$reflection = new ReflectionClass( $storage );
		$method     = $reflection->getMethod( 'getTransientNamePacketaCheckoutData' );
		$method->setAccessible( true );

		return (string) $method->invoke( $storage );
	}

	public function testTransientStorage(): void {
		$container = IntegrationTestsHelper::getContainer();
		/** @var CheckoutStorage $checkoutStorage */
		$checkoutStorage = $container->getByType( CheckoutStorage::class );

		$dummyData = [
			'dummyKey' => [ 'dummyDataKey' => 'dummyDataValue' ],
		];

		$checkoutStorage->setTransient( $dummyData );
		$this->assertSame( $dummyData, $checkoutStorage->getFromTransient() );

		$checkoutStorage->deleteTransient();
		$this->assertFalse( $checkoutStorage->getFromTransient() );
	}

	public static function enrichmentProvider(): array {
		$ratePp          = 'dummyRate1';
		$rateHd          = 'dummyRate2';
		$rateCar         = 'dummyRate3';
		$rateCarrierOnly = 'dummyRate4';

		return [
			'pickup point'       => [
				'data'           => [
					$ratePp => [
						Attr::POINT_ID     => 'PP123',
						Attr::POINT_NAME   => 'Point Name',
						Attr::POINT_CITY   => 'City',
						Attr::POINT_ZIP    => '12345',
						Attr::POINT_STREET => 'Street 1',
						Attr::POINT_PLACE  => 'Business XYZ',
						Attr::CARRIER_ID   => 'carrier_1',
						Attr::POINT_URL    => 'dummyUrl',
					],
				],
				'rateId'         => $ratePp,
				'attributes'     => Attr::$pickupPointAttributes,
				'additionalKeys' => [ Attr::CARRIER_ID ],
			],
			'home delivery'      => [
				'data'           => [
					$rateHd => [
						Attr::ADDRESS_IS_VALIDATED => '1',
						Attr::ADDRESS_HOUSE_NUMBER => '10',
						Attr::ADDRESS_STREET       => 'Home Street',
						Attr::ADDRESS_CITY         => 'Praha',
						Attr::ADDRESS_POST_CODE    => '19000',
						Attr::ADDRESS_COUNTY       => 'Praha',
						Attr::ADDRESS_COUNTRY      => 'CZ',
						Attr::ADDRESS_LATITUDE     => '50.087',
						Attr::ADDRESS_LONGITUDE    => '14.421',
					],
				],
				'rateId'         => $rateHd,
				'attributes'     => Attr::$homeDeliveryAttributes,
				'additionalKeys' => [],
			],
			'car delivery'       => [
				'data'           => [
					$rateCar => [
						Attr::CAR_DELIVERY_ID        => 'cd_123',
						Attr::ADDRESS_STREET         => 'Car Street',
						Attr::ADDRESS_HOUSE_NUMBER   => '1',
						Attr::ADDRESS_CITY           => 'City',
						Attr::ADDRESS_POST_CODE      => '19000',
						Attr::ADDRESS_COUNTRY        => 'CZ',
						Attr::EXPECTED_DELIVERY_FROM => '2025-01-01',
						Attr::EXPECTED_DELIVERY_TO   => '2025-01-02',
					],
				],
				'rateId'         => $rateCar,
				'attributes'     => Attr::$carDeliveryAttributes,
				'additionalKeys' => [],
			],
			'hd carrier id only' => [
				'data'           => [
					$rateCarrierOnly => [
						Attr::CARRIER_ID => 'dummyCarrier',
					],
				],
				'rateId'         => $rateCarrierOnly,
				'attributes'     => [ [ 'name' => Attr::CARRIER_ID ] ],
				'additionalKeys' => [],
			],
		];
	}

	/**
	 * @dataProvider enrichmentProvider
	 */
	public function testEnrichmentFromTransientCoversAllVariants( array $data, string $rateId, array $attributes, array $additionalKeys ): void {
		$storage = new CheckoutStorage( self::buildRequest(), new WpAdapter(), new WcAdapter() );
		$storage->setTransient( $data );

		$updatedData = $storage->getPostDataIncludingStoredData( $rateId );
		foreach ( $attributes as $attributeProperties ) {
			$name = $attributeProperties['name'];
			$this->assertArrayHasKey( $name, $updatedData );
			$this->assertSame( $data[ $rateId ][ $name ], $updatedData[ $name ] );
		}
		foreach ( $additionalKeys as $additionalKey ) {
			$this->assertArrayHasKey( $additionalKey, $updatedData );
			$this->assertSame( $data[ $rateId ][ $additionalKey ], $updatedData[ $additionalKey ] );
		}
	}

	public static function passthroughProvider(): array {
		return [
			'saved null'                 => [
				'savedData'  => null,
				'postData'   => [ 'a' => 'b' ],
				'chosenRate' => 'rateA',
			],
			'saved array without chosen' => [
				'savedData'  => [ 'other' => [] ],
				'postData'   => [ 'x' => 'y' ],
				'chosenRate' => 'rateB',
			],
			'saved chosen not array'     => [
				'savedData'  => [ 'rateC' => 'unexpectedString' ],
				'postData'   => [ 'z' => 'w' ],
				'chosenRate' => 'rateC',
			],
		];
	}

	/**
	 * @dataProvider passthroughProvider
	 */
	public function testPassthroughReturnsPostDataWhenSavedDataInvalid( $savedData, array $postData, string $chosenRate ): void {
		$checkoutStorage = new CheckoutStorage( self::buildRequest( $postData ), new WpAdapter(), new WcAdapter() );
		if ( $savedData !== null ) {
			$checkoutStorage->setTransient( $savedData );
		}
		$result = $checkoutStorage->getPostDataIncludingStoredData( $chosenRate );
		$this->assertSame( $postData, $result );
	}

	public static function invalidCombinationProvider(): array {
		return [
			'empty post array and saved array' => [
				'request'       => self::buildRequest(),
				'setEmptySaved' => true,
				'chosenRate'    => 'nonexistentRate',
			],
			'non array post and no saved'      => [
				'request'       => self::buildRequestWithRawPost( 'dummyString' ),
				'setEmptySaved' => false,
				'chosenRate'    => 'rateX',
			],
		];
	}

	/**
	 * @dataProvider invalidCombinationProvider
	 */
	public function testInvalidCombinationLogsWarningAndReturnsEmpty( Request $request, bool $setEmptySaved, string $chosenRate ): void {
		$checkoutStorage = new CheckoutStorage( $request, new WpAdapter(), new WcAdapter() );

		if ( $setEmptySaved ) {
			$checkoutStorage->setTransient( [] );
		}

		$updatedData = $checkoutStorage->getPostDataIncludingStoredData( $chosenRate, 123 );
		$this->assertSame( [], $updatedData, 'Expected empty array on invalid combination.' );
	}

	public function testGuestVsLoggedInTransientKeys(): void {
		wp_logout();

		$checkoutStorage = new CheckoutStorage( self::buildRequest(), new WpAdapter(), new WcAdapter() );
		$guestKey        = $this->invokeTransientName( $checkoutStorage );

		$userEmail = 'test@example.com';
		$wpUserId  = wp_create_user( 'testuser', 'testpassword', $userEmail );
		if ( is_wp_error( $wpUserId ) ) {
			$wpUser   = get_user_by( 'email', $userEmail );
			$wpUserId = $wpUser->ID;
		}
		wp_set_current_user( $wpUserId );
		wp_set_auth_cookie( $wpUserId );

		$loggedKey = $this->invokeTransientName( $checkoutStorage );
		$this->assertNotSame( $guestKey, $loggedKey, 'Transient keys must differ for guest vs logged-in.' );

		wp_logout();
	}
}
