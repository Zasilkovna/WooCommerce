<?php

declare( strict_types=1 );

namespace Tests\Core\Validator;

use Packetery\Core\Validator\Address;
use Packetery\Core\Validator\Order;
use Packetery\Core\Validator\Size;
use PHPUnit\Framework\TestCase;
use Tests\Core\DummyFactory;

class OrderTest extends TestCase {

	public function testValidation(): void {
		$addressValidator      = new Address();
		$sizeValidator         = new Size();
		$validatorTranslations = [
			Order::ERROR_TRANSLATION_KEY_NUMBER                     => 'Order number is not set.',
			Order::ERROR_TRANSLATION_KEY_NAME                       => 'Customer name is not set.',
			Order::ERROR_TRANSLATION_KEY_VALUE                      => 'Order value is not set.',
			Order::ERROR_TRANSLATION_KEY_PICKUP_POINT_OR_CARRIER_ID => 'Pickup point or carrier id is not set.',
			Order::ERROR_TRANSLATION_KEY_ESHOP                      => 'Sender label is not set.',
			Order::ERROR_TRANSLATION_KEY_WEIGHT                     => 'Weight is not set or is zero.',
			Order::ERROR_TRANSLATION_KEY_ADDRESS                    => 'Address is not set or is incomplete.',
			Order::ERROR_TRANSLATION_KEY_HEIGHT                     => 'Order height is not set.',
			Order::ERROR_TRANSLATION_KEY_WIDTH                      => 'Order width is not set.',
			Order::ERROR_TRANSLATION_KEY_LENGTH                     => 'Order length is set.',
			Order::ERROR_TRANSLATION_KEY_CUSTOMS_DECLARATION        => 'Customs declaration is not set.',
		];
		$validator = new Order( $addressValidator, $sizeValidator, $validatorTranslations );

		$dummyOrder = DummyFactory::createOrderCzPp();
		$dummyOrder->setPickupPoint( DummyFactory::createPickupPoint() );
		self::assertSame( [], $validator->validate( $dummyOrder ) );
		self::assertTrue( $validator->isValid( $dummyOrder ) );

		$dummyOrder->setNumber( '' );
		$dummyOrder->setName( '' );
		$validationResult = $validator->validate( $dummyOrder );
		self::assertArrayHasKey( Order::ERROR_TRANSLATION_KEY_NUMBER, $validationResult );
		self::assertArrayHasKey( Order::ERROR_TRANSLATION_KEY_NAME, $validationResult );
		self::assertCount( 2, $validationResult );
		self::assertFalse( $validator->isValid( $dummyOrder ) );

		$dummyOrderHd        = DummyFactory::createOrderCzHdIncomplete();
		$dummyOrderHdInvalid = clone $dummyOrderHd;
		$dummyOrderHd->setSize( DummyFactory::createSize() );
		$dummyOrderHd->setDeliveryAddress( DummyFactory::createAddress() );
		self::assertSame( [], $validator->validate( $dummyOrderHd ) );

		self::assertCount( 4, $validator->validate( $dummyOrderHdInvalid ) );

		$validator = new Order( $addressValidator, $sizeValidator, [ Order::ERROR_TRANSLATION_KEY_NAME => ''] );
		self::assertFalse( $validator->isValid( $dummyOrder ) );
	}

}
