<?php

declare( strict_types=1 );

namespace Core\Entity;

use Packetery\Core\Helper;
use PHPUnit\Framework\TestCase;
use Tests\DummyFactory;

class CustomsDeclarationItemTest extends TestCase {

	const DUMMY_FILE_CONTENT = 'Dummy file content';

	public function testSettersAndGetters() {
		$customsDeclarationItem = DummyFactory::createCustomsDeclarationItem();

		$dummyId = 'dummyId';
		$customsDeclarationItem->setId( $dummyId );
		$this->assertSame( $dummyId, $customsDeclarationItem->getId() );

		$dummyProductName = 'dummyProductName';
		$customsDeclarationItem->setProductName( $dummyProductName );
		$this->assertSame( $dummyProductName, $customsDeclarationItem->getProductName() );

		$customsDeclarationItem->setIsFoodOrBook( true );
		$this->assertTrue( $customsDeclarationItem->isFoodOrBook() );

		$customsDeclarationItem->setIsVoc( true );
		$this->assertTrue( $customsDeclarationItem->isVoc() );

		$this->assertIsString($customsDeclarationItem->getCountryOfOrigin());
		$this->assertIsString($customsDeclarationItem->getCustomsCode());
		$this->assertIsString($customsDeclarationItem->getCustomsDeclarationId());
		$this->assertIsString($customsDeclarationItem->getProductNameEn());
		$this->assertIsString($customsDeclarationItem->getCountryOfOrigin());
		$this->assertIsInt($customsDeclarationItem->getUnitsCount());
		$this->assertIsFloat($customsDeclarationItem->getValue());
		$this->assertIsFloat($customsDeclarationItem->getWeight());
	}

}
