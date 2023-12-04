<?php

declare( strict_types=1 );

namespace Tests\Core\Entity;

use Tests\Core\DummyFactory;
use PHPUnit\Framework\TestCase;

class CustomsDeclarationItemTest extends TestCase {

	public function testSettersAndGetters(): void {
		$customsDeclarationItem = DummyFactory::createCustomsDeclarationItem();

		$dummyId = 'dummyId';
		$customsDeclarationItem->setId( $dummyId );
		self::assertSame( $dummyId, $customsDeclarationItem->getId() );

		$dummyProductName = 'dummyProductName';
		$customsDeclarationItem->setProductName( $dummyProductName );
		self::assertSame( $dummyProductName, $customsDeclarationItem->getProductName() );

		$customsDeclarationItem->setIsFoodOrBook( true );
		self::assertTrue( $customsDeclarationItem->isFoodOrBook() );

		$customsDeclarationItem->setIsVoc( true );
		self::assertTrue( $customsDeclarationItem->isVoc() );

		self::assertIsString( $customsDeclarationItem->getCountryOfOrigin() );
		self::assertIsString( $customsDeclarationItem->getCustomsCode() );
		self::assertIsString( $customsDeclarationItem->getCustomsDeclarationId() );
		self::assertIsString( $customsDeclarationItem->getProductNameEn() );
		self::assertIsString( $customsDeclarationItem->getCountryOfOrigin() );
		self::assertIsInt( $customsDeclarationItem->getUnitsCount() );
		self::assertIsFloat( $customsDeclarationItem->getValue() );
		self::assertIsFloat( $customsDeclarationItem->getWeight() );
	}

}
