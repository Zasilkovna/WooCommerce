<?php

declare( strict_types=1 );

namespace Tests\Module\Checkout;

use Packetery\Module\Checkout\CheckoutStorage;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Transients;
use Packetery\Nette\Http\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class CheckoutStorageTest extends TestCase {
	private WpAdapter&MockObject $wpAdapterMock;

	private function createCheckoutStorage(): CheckoutStorage {
		$this->wpAdapterMock = $this->createMock( WpAdapter::class );

		return new CheckoutStorage(
			$this->createMock( Request::class ),
			$this->wpAdapterMock,
			$this->createMock( WcAdapter::class )
		);
	}

	public function testIsKeyPresentInSavedDataButNotInPostData(): void {
		$checkoutStorage = $this->createCheckoutStorage();

		$reflection = new ReflectionClass( $checkoutStorage );

		$privateIsKeyPresentInSavedDataButNotInPostData = $reflection->getMethod( 'isKeyPresentInSavedDataButNotInPostData' );
		$privateIsKeyPresentInSavedDataButNotInPostData->setAccessible( true );

		$this->assertTrue(
			$privateIsKeyPresentInSavedDataButNotInPostData->invoke(
				$checkoutStorage,
				[ 'key1' => 'value1' ],
				[ 'key2' => 'value2' ],
				'key2'
			),
			'Key is not present in checkoutData but present in savedCarrierData.'
		);

		$this->assertFalse(
			$privateIsKeyPresentInSavedDataButNotInPostData->invoke(
				$checkoutStorage,
				[
					'key1' => 'value1',
					'key2' => 'value2',
				],
				[ 'key2' => 'value2' ],
				'key2'
			),
			'Key is present in both checkoutData and savedCarrierData.'
		);

		$this->assertFalse(
			$privateIsKeyPresentInSavedDataButNotInPostData->invoke(
				$checkoutStorage,
				[ 'key2' => '' ],
				[ 'key2' => '' ],
				'key2'
			),
			'Key is present in both, but both are empty or not set.'
		);

		$this->assertTrue(
			$privateIsKeyPresentInSavedDataButNotInPostData->invoke(
				$checkoutStorage,
				[],
				[ 'key2' => 'value2' ],
				'key2'
			),
			'Key is missing in checkoutData but present in savedCarrierData.'
		);

		$this->assertFalse(
			$privateIsKeyPresentInSavedDataButNotInPostData->invoke(
				$checkoutStorage,
				[ 'key2' => 'value2' ],
				[],
				'key2'
			),
			'Key is present in checkoutData but missing in savedCarrierData.'
		);

		$this->assertFalse(
			$privateIsKeyPresentInSavedDataButNotInPostData->invoke(
				$checkoutStorage,
				[],
				[],
				'key2'
			),
			'Key is missing in both arrays.'
		);
	}

	public function testMigrateGuestSessionToUserSession(): void {
		$dummyGuestSessionId = 'dummy_guest_session_123';
		$oldTransientId      = Transients::CHECKOUT_DATA_PREFIX . $dummyGuestSessionId;
		$dummyTransientData  = [
			'shipping_method_1' => [
				'point_id'   => '123',
				'carrier_id' => '456',
			],
		];

		$checkoutStorage = $this->createCheckoutStorage();

		$this->wpAdapterMock->expects( $this->once() )
			->method( 'getTransient' )
			->with( $oldTransientId )
			->willReturn( $dummyTransientData );

		$this->wpAdapterMock->expects( $this->once() )
							->method( 'setTransient' );

		$this->wpAdapterMock->expects( $this->once() )
			->method( 'deleteTransient' )
			->with( $oldTransientId );

		$checkoutStorage->migrateGuestSessionToUserSession( $dummyGuestSessionId );
	}

	public function testMigrateGuestSessionToUserSessionNoDataSaved(): void {
		$dummyGuestSessionId = 'dummy_guest_session_123';
		$oldTransientId      = Transients::CHECKOUT_DATA_PREFIX . $dummyGuestSessionId;
		$checkoutStorage     = $this->createCheckoutStorage();

		$this->wpAdapterMock->expects( $this->once() )
							->method( 'getTransient' )
							->with( $oldTransientId )
							->willReturn( false );

		$this->wpAdapterMock->expects( $this->never() )
							->method( 'setTransient' );

		$this->wpAdapterMock->expects( $this->never() )
							->method( 'deleteTransient' );

		$checkoutStorage->migrateGuestSessionToUserSession( $dummyGuestSessionId );
	}

	public static function transientDataProvider(): array {
		return [
			'do not set, delete v1' => [
				'transientData' => [],
			],
			'do not set, delete v2' => [
				'transientData' => 123,
			],
			'do not set, delete v3' => [
				'transientData' => 123.45,
			],
			'do not set, delete v4' => [
				'transientData' => 'foo',
			],
			'do not set, delete v5' => [
				'transientData' => '',
			],
			'do not set, delete v6' => [
				'transientData' => new \stdClass(),
			],
			'do not set, delete v7' => [
				'transientData' => null,
			],
			'do not set, delete v8' => [
				'transientData' => true,
			],
		];
	}

	/**
	 * @dataProvider transientDataProvider
	 */
	public function testMigrateGuestSessionToUserSessionEmptyOrWrongTypeDataSaved( $transientData ): void {
		$dummyGuestSessionId = 'dummy_guest_session_123';
		$oldTransientId      = Transients::CHECKOUT_DATA_PREFIX . $dummyGuestSessionId;
		$checkoutStorage     = $this->createCheckoutStorage();

		$this->wpAdapterMock->expects( $this->once() )
							->method( 'getTransient' )
							->with( $oldTransientId )
							->willReturn( $transientData );

		$this->wpAdapterMock->expects( $this->never() )
							->method( 'setTransient' );

		$this->wpAdapterMock->expects( $this->once() )
							->method( 'deleteTransient' )
							->with( $oldTransientId );

		$checkoutStorage->migrateGuestSessionToUserSession( $dummyGuestSessionId );
	}

	public static function validateDataStructureProvider(): array {
		return [
			'valid structure'             => [
				'data'     => [
					'shipping_method_1' => [
						'point_id'   => '123',
						'carrier_id' => '456',
					],
					'shipping_method_2' => [
						'address_validated' => true,
						'street'            => 'Main Street',
					],
				],
				'expected' => true,
			],
			'empty array'                 => [
				'data'     => [],
				'expected' => false,
			],
			'not an array'                => [
				'data'     => 'not an array',
				'expected' => false,
			],
			'numeric key in top level'    => [
				'data'     => [
					0 => [
						'point_id' => '123',
					],
				],
				'expected' => false,
			],
			'value not an array'          => [
				'data'     => [
					'shipping_method_1' => 'not an array',
				],
				'expected' => false,
			],
			'numeric key in nested array' => [
				'data'     => [
					'shipping_method_1' => [
						0 => 'numeric key',
					],
				],
				'expected' => false,
			],
			'mixed valid and invalid'     => [
				'data'     => [
					'shipping_method_1' => [
						'point_id' => '123',
					],
					0                   => [
						'carrier_id' => '456',
					],
				],
				'expected' => false,
			],
		];
	}

	/**
	 * @dataProvider validateDataStructureProvider
	 */
	public function testValidateDataStructure( $data, bool $expected ): void {
		$checkoutStorage = $this->createCheckoutStorage();

		$this->assertSame( $checkoutStorage->validateDataStructure( $data ), $expected );
	}
}
