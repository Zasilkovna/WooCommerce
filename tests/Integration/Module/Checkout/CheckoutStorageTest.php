<?php

declare( strict_types=1 );

namespace Tests\Integration\Module\Checkout;

use Packetery\Module\Checkout\CheckoutStorage;
use PHPUnit\Framework\TestCase;
use Tests\Integration\IntegrationTestsHelper;

class CheckoutStorageTest extends TestCase {
	public function testSetTransient(): void {
		$container = IntegrationTestsHelper::getContainer();
		/**
		 * @var CheckoutStorage $checkoutStorage
		 */
		$checkoutStorage = $container->getByType( CheckoutStorage::class );

		$dummyData = [
			'dummyKey' => [
				'dummyDataKey' => 'dummyDataValue',
			],
		];

		$checkoutStorage->setTransient( $dummyData );

		$this->assertSame( $dummyData, $checkoutStorage->getFromTransient() );
	}
}
