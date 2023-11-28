<?php

declare( strict_types=1 );

namespace Tests\Core\Entity;

use Packetery\Core\Entity\PacketStatus;
use Packetery\Core\Helper;
use PHPUnit\Framework\TestCase;
use Tests\DummyFactory;

class OrderTest extends TestCase {

	public function testSettersAndGetters() {
		$order = DummyFactory::createOrderCzPp();

		$dummyCustomsDeclaration = DummyFactory::createCustomsDeclaration();
		$order->setCustomsDeclaration( $dummyCustomsDeclaration );
		$this->assertSame( $dummyCustomsDeclaration, $order->getCustomsDeclaration() );
		$this->assertTrue( $order->hasCustomsDeclaration() );

		$dummyCustomNumber = 'dummyNumber';
		$order->setCustomNumber( $dummyCustomNumber );
		$this->assertSame( $dummyCustomNumber, $order->getCustomNumber() );

		$dummyAddress = DummyFactory::createAddress();
		$order->setDeliveryAddress( $dummyAddress );
		$order->setAddressValidated( true );
		$this->assertTrue( $order->isAddressValidated() );
		$this->assertSame( $dummyAddress, $order->getValidatedDeliveryAddress() );

		$order->setAdultContent( true );
		$this->assertTrue( $order->containsAdultContent() );

		$order->setIsLabelPrinted( true );
		$this->assertTrue( $order->isLabelPrinted() );

		$order->setIsExported( true );
		$this->assertTrue( $order->isExported() );

		$dummyCurrency = 'EUR';
		$order->setCurrency( $dummyCurrency );
		$this->assertSame( $dummyCurrency, $order->getCurrency() );

		$this->assertNull( $order->getPickupPointOrCarrierId() );

		$dummyPickupPoint = DummyFactory::createPickupPoint();
		$order->setPickupPoint( $dummyPickupPoint );
		$this->assertSame( $dummyPickupPoint, $order->getPickupPoint() );
		$this->assertTrue( $order->isPickupPointDelivery() );
		$this->assertTrue( $order->isPacketaInternalPickupPoint() );

		$dummyPacketId = 'dummyPacketId';
		$order->setPacketId( $dummyPacketId );
		$this->assertSame( $dummyPacketId, $order->getPacketId() );
		$this->assertSame( 'Z' . $dummyPacketId, $order->getPacketBarcode() );
		$this->assertIsString( $order->getPacketTrackingUrl() );

		$dummyPacketStatus = PacketStatus::DELIVERED;
		$order->setPacketStatus( $dummyPacketStatus );
		$this->assertSame( $dummyPacketStatus, $order->getPacketStatus() );
		$this->assertTrue( $order->isPacketClaimCreationPossible() );

		$dummyPacketClaimId = 'dummyPacketClaimId';
		$order->setPacketClaimId( $dummyPacketClaimId );
		$this->assertSame( $dummyPacketClaimId, $order->getPacketClaimId() );
		$this->assertTrue( $order->isPacketClaimLabelPrintPossible() );

		$dummyPacketClaimPassword = 'dummyPassword';
		$order->setPacketClaimPassword( $dummyPacketClaimPassword );
		$this->assertSame( $dummyPacketClaimPassword, $order->getPacketClaimPassword() );

		$dummyWeight = 1.25;
		$order->setWeight( $dummyWeight );
		$this->assertSame( $dummyWeight, $order->getWeight() );
		$this->assertTrue( $order->hasManualWeight() );

		$dummyCalculatedWeight = 1.75;
		$order->setCalculatedWeight( $dummyCalculatedWeight );
		$this->assertSame( $dummyCalculatedWeight, $order->getCalculatedWeight() );

		$dummySurname = 'dummySurname';
		$order->setSurname( $dummySurname );
		$this->assertSame( $dummySurname, $order->getSurname() );

		$dummyEmail = 'dummy@test.tld';
		$order->setEmail( $dummyEmail );
		$this->assertSame( $dummyEmail, $order->getEmail() );

		$dummyNote = 'dummyNote';
		$order->setNote( $dummyNote );
		$this->assertSame( $dummyNote, $order->getNote() );

		$dummyPhone = '123456789';
		$order->setPhone( $dummyPhone );
		$this->assertSame( $dummyPhone, $order->getPhone() );

		$dummyCarrierNumber = 'dummyCarrierNumber';
		$order->setCarrierNumber( $dummyCarrierNumber );
		$this->assertSame( $dummyCarrierNumber, $order->getCarrierNumber() );

		$dummyShippingCountry = '';
		$order->setShippingCountry( $dummyShippingCountry );
		$this->assertNull( $order->getShippingCountry() );

		$dummyShippingCountry = 'de';
		$order->setShippingCountry( $dummyShippingCountry );
		$this->assertSame( $dummyShippingCountry, $order->getShippingCountry() );

		$this->assertFalse( $order->hasCod() );
		$dummyCod = 1234.5;
		$order->setCod( $dummyCod );
		$this->assertSame( $dummyCod, $order->getCod() );
		$this->assertTrue( $order->hasCod() );

		$dummyDateImmutable = Helper::now();
		$order->setDeliverOn( $dummyDateImmutable );
		$this->assertSame( $dummyDateImmutable, $order->getDeliverOn() );

		$dummyLastApiErrorMessage = 'dummyMessage';
		$order->setLastApiErrorMessage( $dummyLastApiErrorMessage );
		$this->assertSame( $dummyLastApiErrorMessage, $order->getLastApiErrorMessage() );
		$order->updateApiErrorMessage( $dummyLastApiErrorMessage );
		$this->assertSame( $dummyLastApiErrorMessage, $order->getLastApiErrorMessage() );

		$order->setLastApiErrorDatetime( $dummyDateImmutable );
		$this->assertSame( $dummyDateImmutable, $order->getLastApiErrorDatetime() );

		$this->assertNull( $order->getLength() );
		$this->assertNull( $order->getWidth() );
		$this->assertNull( $order->getHeight() );
		$order->setSize( DummyFactory::createSize() );
		$this->assertIsFloat( $order->getLength() );
		$this->assertIsFloat( $order->getWidth() );
		$this->assertIsFloat( $order->getHeight() );
	}

}
