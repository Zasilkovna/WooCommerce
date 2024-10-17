<?php

declare( strict_types=1 );

namespace Tests\Core\Entity;

use Packetery\Core\Entity\PacketStatus;
use Packetery\Core\CoreHelper;
use PHPUnit\Framework\TestCase;
use Tests\Core\DummyFactory;

class OrderTest extends TestCase {

	public function testSettersAndGetters(): void {
		$order             = DummyFactory::createOrderCzPp();
		$carDeliveryOrder  = DummyFactory::createOrderCzCdIncomplete();
		$homeDeliveryOrder = DummyFactory::createOrderCzHdIncomplete();

		self::assertTrue( $carDeliveryOrder->isCarDelivery() );
		self::assertTrue( $homeDeliveryOrder->isHomeDelivery() );

		$dummyCarDeliveryId = 'qwe123';
		$carDeliveryOrder->setCarDeliveryId( $dummyCarDeliveryId );
		self::assertSame( $dummyCarDeliveryId, $carDeliveryOrder->getCarDeliveryId() );

		$dummyCustomsDeclaration = DummyFactory::createCustomsDeclaration();
		$order->setCustomsDeclaration( $dummyCustomsDeclaration );
		self::assertSame( $dummyCustomsDeclaration, $order->getCustomsDeclaration() );
		self::assertTrue( $order->hasCustomsDeclaration() );

		$dummyCustomNumber = 'dummyNumber';
		$order->setCustomNumber( $dummyCustomNumber );
		self::assertSame( $dummyCustomNumber, $order->getCustomNumber() );

		$dummyAddress = DummyFactory::createAddress();
		$order->setDeliveryAddress( $dummyAddress );
		$order->setAddressValidated( true );
		self::assertTrue( $order->isAddressValidated() );
		self::assertSame( $dummyAddress, $order->getValidatedDeliveryAddress() );

		$order->setAdultContent( true );
		self::assertTrue( $order->containsAdultContent() );

		$order->setIsLabelPrinted( true );
		self::assertTrue( $order->isLabelPrinted() );

		$order->setIsExported( true );
		self::assertTrue( $order->isExported() );

		$dummyCurrency = 'EUR';
		$order->setCurrency( $dummyCurrency );
		self::assertSame( $dummyCurrency, $order->getCurrency() );

		self::assertNull( $order->getPickupPointOrCarrierId() );

		$dummyPickupPoint = DummyFactory::createPickupPoint();
		$order->setPickupPoint( $dummyPickupPoint );
		self::assertSame( $dummyPickupPoint, $order->getPickupPoint() );
		self::assertTrue( $order->isPickupPointDelivery() );
		self::assertTrue( $order->isPacketaInternalPickupPoint() );
		self::assertTrue( $order->allowsAdultContent() );

		$dummyPacketId = 'dummyPacketId';
		$order->setPacketId( $dummyPacketId );
		self::assertSame( $dummyPacketId, $order->getPacketId() );
		self::assertSame( 'Z' . $dummyPacketId, $order->getPacketBarcode() );
		self::assertIsString( $order->getPacketTrackingUrl() );

		$dummyPacketStatus = PacketStatus::DELIVERED;
		$order->setPacketStatus( $dummyPacketStatus );
		self::assertSame( $dummyPacketStatus, $order->getPacketStatus() );
		self::assertTrue( $order->isPacketClaimCreationPossible() );

		$dummyPacketClaimId = 'dummyPacketClaimId';
		$order->setPacketClaimId( $dummyPacketClaimId );
		self::assertSame( $dummyPacketClaimId, $order->getPacketClaimId() );
		self::assertSame( 'Z' . $dummyPacketClaimId, $order->getPacketClaimBarcode() );
		self::assertTrue( $order->isPacketClaimLabelPrintPossible() );

		$dummyPacketClaimPassword = 'dummyPassword';
		$order->setPacketClaimPassword( $dummyPacketClaimPassword );
		self::assertSame( $dummyPacketClaimPassword, $order->getPacketClaimPassword() );

		$dummyWeight = 1.25;
		$order->setWeight( $dummyWeight );
		self::assertSame( $dummyWeight, $order->getWeight() );
		self::assertTrue( $order->hasManualWeight() );

		$dummyCalculatedWeight = 1.75;
		$order->setCalculatedWeight( $dummyCalculatedWeight );
		self::assertSame( $dummyCalculatedWeight, $order->getCalculatedWeight() );

		$dummySurname = 'dummySurname';
		$order->setSurname( $dummySurname );
		self::assertSame( $dummySurname, $order->getSurname() );

		$dummyEmail = 'dummy@test.tld';
		$order->setEmail( $dummyEmail );
		self::assertSame( $dummyEmail, $order->getEmail() );

		$dummyNote = 'dummyNote';
		$order->setNote( $dummyNote );
		self::assertSame( $dummyNote, $order->getNote() );

		$dummyPhone = '123456789';
		$order->setPhone( $dummyPhone );
		self::assertSame( $dummyPhone, $order->getPhone() );

		$dummyCarrierNumber = 'dummyCarrierNumber';
		$order->setCarrierNumber( $dummyCarrierNumber );
		self::assertSame( $dummyCarrierNumber, $order->getCarrierNumber() );

		$dummyShippingCountry = '';
		$order->setShippingCountry( $dummyShippingCountry );
		self::assertNull( $order->getShippingCountry() );

		$dummyShippingCountry = 'de';
		$order->setShippingCountry( $dummyShippingCountry );
		self::assertSame( $dummyShippingCountry, $order->getShippingCountry() );

		self::assertFalse( $order->hasCod() );
		$dummyCod = 1234.5;
		$order->setCod( $dummyCod );
		self::assertSame( $dummyCod, $order->getCod() );
		self::assertTrue( $order->hasCod() );

		$dummyDateImmutable = CoreHelper::now();
		$order->setDeliverOn( $dummyDateImmutable );
		self::assertSame( $dummyDateImmutable, $order->getDeliverOn() );

		$dummyLastApiErrorMessage = 'dummyMessage';
		$order->setLastApiErrorMessage( $dummyLastApiErrorMessage );
		self::assertSame( $dummyLastApiErrorMessage, $order->getLastApiErrorMessage() );
		$order->updateApiErrorMessage( $dummyLastApiErrorMessage );
		self::assertSame( $dummyLastApiErrorMessage, $order->getLastApiErrorMessage() );

		$order->setLastApiErrorDatetime( $dummyDateImmutable );
		self::assertSame( $dummyDateImmutable, $order->getLastApiErrorDatetime() );

		self::assertNull( $order->getLength() );
		self::assertNull( $order->getWidth() );
		self::assertNull( $order->getHeight() );
		$order->setSize( DummyFactory::createSize() );
		self::assertIsFloat( $order->getLength() );
		self::assertIsFloat( $order->getWidth() );
		self::assertIsFloat( $order->getHeight() );
	}

}
