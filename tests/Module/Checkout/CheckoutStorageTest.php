<?php

declare( strict_types=1 );

namespace Tests\Module\Checkout;

use Packetery\Module\Checkout\CheckoutStorage;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
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
		$oldTransientId      = CheckoutStorage::TRANSIENT_CHECKOUT_DATA_PREFIX . $dummyGuestSessionId;
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
			->method( 'deleteTransient' )
			->with( $oldTransientId );

		$checkoutStorage->migrateGuestSessionToUserSession( $dummyGuestSessionId );
	}
}
