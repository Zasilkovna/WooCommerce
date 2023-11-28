<?php

declare( strict_types=1 );

namespace Tests\Core\Validator;

use Packetery\Core\Validator\Address;
use Packetery\Core\Validator\Order;
use Packetery\Core\Validator\Size;
use PHPUnit\Framework\TestCase;
use Tests\DummyFactory;

class OrderTest extends TestCase {

	public function testValidation() {
		$addressValidator      = new Address();
		$sizeValidator         = new Size();
		$validatorTranslations = [
			Order::ERROR_TRANSLATION_KEY_NUMBER                     => 'Order number is not set.',
			// To test missing translation.
			//Order::ERROR_TRANSLATION_KEY_NAME                       => 'Customer name is not set.',
			Order::ERROR_TRANSLATION_KEY_VALUE                      => 'Order value is not set.',
			Order::ERROR_TRANSLATION_KEY_PICKUP_POINT_OR_CARRIER_ID => 'Pickup point or carrier id is not set.',
			Order::ERROR_TRANSLATION_KEY_ESHOP                      => 'Sender label is not set.',
			Order::ERROR_TRANSLATION_KEY_WEIGHT                     => 'Weight is not set or is zero.',
			Order::ERROR_TRANSLATION_KEY_ADDRESS                    => 'Address is not set or is incomplete.',
			Order::ERROR_TRANSLATION_KEY_SIZE                       => 'Order dimensions are not set.',
			Order::ERROR_TRANSLATION_KEY_CUSTOMS_DECLARATION        => 'Customs declaration is not set.',
		];
		$validator             = new Order( $addressValidator, $sizeValidator, $validatorTranslations );

		$dummyOrder = DummyFactory::createOrderCzPp();
		$dummyOrder->setPickupPoint( DummyFactory::createPickupPoint() );
		$this->assertSame([],  $validator->validate( $dummyOrder ) );
		$this->assertTrue(  $validator->isValid( $dummyOrder ) );

		$dummyOrder->setNumber( '' );
		$dummyOrder->setName( '' );
		$this->assertCount( 2, $validator->validate( $dummyOrder ) );
		$this->assertFalse(  $validator->isValid( $dummyOrder ) );

		$dummyOrderHd = DummyFactory::createOrderCzHdIncomplete();
		$dummyOrderHdInvalid = clone $dummyOrderHd;
		$dummyOrderHd->setSize(DummyFactory::createSize());
		$dummyOrderHd->setDeliveryAddress(DummyFactory::createAddress());
		$this->assertSame([],  $validator->validate( $dummyOrderHd ) );

		$this->assertCount( 2, $validator->validate( $dummyOrderHdInvalid ) );
	}

}
