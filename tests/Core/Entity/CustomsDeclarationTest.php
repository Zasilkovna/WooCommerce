<?php

declare( strict_types=1 );

namespace Core\Entity;

use Packetery\Core\Helper;
use PHPUnit\Framework\TestCase;
use Tests\DummyFactory;

class CustomsDeclarationTest extends TestCase {

	const DUMMY_FILE_CONTENT = 'Dummy file content';

	public function testSettersAndGetters() {
		$customsDeclaration = DummyFactory::createCustomsDeclaration();

		$dummyId = 'dummyId';
		$customsDeclaration->setId( $dummyId );
		$this->assertSame( $dummyId, $customsDeclaration->getId() );

		$dummyDeliveryCost = 124.5;
		$customsDeclaration->setDeliveryCost( $dummyDeliveryCost );
		$this->assertSame( $dummyDeliveryCost, $customsDeclaration->getDeliveryCost() );

		$dummyEad = 'dummyEad';
		$customsDeclaration->setEad( $dummyEad );
		$this->assertSame( $dummyEad, $customsDeclaration->getEad() );

		$customsDeclaration->setEadFile( function() {
			return self::DUMMY_FILE_CONTENT;
		}, true );
		$this->assertSame( self::DUMMY_FILE_CONTENT, $customsDeclaration->getEadFile() );
		$this->assertTrue( $customsDeclaration->hasEadFileContent() );

		$dummyEadFileId = 'dummyEadFileId';
		$customsDeclaration->setEadFileId( $dummyEadFileId );
		$this->assertSame( $dummyEadFileId, $customsDeclaration->getEadFileId() );

		$customsDeclaration->setInvoiceFile( function() {
			return self::DUMMY_FILE_CONTENT;
		}, true );
		$this->assertSame( self::DUMMY_FILE_CONTENT, $customsDeclaration->getInvoiceFile() );
		$this->assertTrue( $customsDeclaration->hasInvoiceFileContent() );

		$dummyInvoiceFileId = 'dummyInvoiceFileId';
		$customsDeclaration->setInvoiceFileId( $dummyInvoiceFileId );
		$this->assertSame( $dummyInvoiceFileId, $customsDeclaration->getInvoiceFileId() );

		$dummyInvoiceIssueDate = Helper::now();
		$customsDeclaration->setInvoiceIssueDate( $dummyInvoiceIssueDate );
		$this->assertSame( $dummyInvoiceIssueDate, $customsDeclaration->getInvoiceIssueDate() );

		$dummyInvoiceNumber = 'dummyInvoiceNumber';
		$customsDeclaration->setInvoiceNumber( $dummyInvoiceNumber );
		$this->assertSame( $dummyInvoiceNumber, $customsDeclaration->getInvoiceNumber() );

		$dummyMrn = 'dummyMrn';
		$customsDeclaration->setMrn( $dummyMrn );
		$this->assertSame( $dummyMrn, $customsDeclaration->getMrn() );

		$this->assertIsString( $customsDeclaration->getOrderId() );
	}

}
