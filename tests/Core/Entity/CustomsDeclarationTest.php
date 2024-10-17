<?php

declare( strict_types=1 );

namespace Tests\Core\Entity;

use Packetery\Core\CoreHelper;
use PHPUnit\Framework\TestCase;
use Tests\Core\DummyFactory;

class CustomsDeclarationTest extends TestCase {

	private const DUMMY_FILE_CONTENT = 'Dummy file content';

	public function testSettersAndGetters(): void {
		$customsDeclaration = DummyFactory::createCustomsDeclaration();

		$dummyId = 'dummyId';
		$customsDeclaration->setId( $dummyId );
		self::assertSame( $dummyId, $customsDeclaration->getId() );

		$dummyDeliveryCost = 124.5;
		$customsDeclaration->setDeliveryCost( $dummyDeliveryCost );
		self::assertSame( $dummyDeliveryCost, $customsDeclaration->getDeliveryCost() );

		self::assertNull( $customsDeclaration->getEadFile() );
		$dummyEad = 'dummyEad';
		$customsDeclaration->setEad( $dummyEad );
		self::assertSame( $dummyEad, $customsDeclaration->getEad() );

		$customsDeclaration->setEadFile( function () {
			return self::DUMMY_FILE_CONTENT;
		}, true );
		self::assertSame( self::DUMMY_FILE_CONTENT, $customsDeclaration->getEadFile() );
		self::assertTrue( $customsDeclaration->hasEadFileContent() );

		$dummyEadFileId = 'dummyEadFileId';
		$customsDeclaration->setEadFileId( $dummyEadFileId );
		self::assertSame( $dummyEadFileId, $customsDeclaration->getEadFileId() );

		self::assertNull( $customsDeclaration->getInvoiceFile() );
		$customsDeclaration->setInvoiceFile( function () {
			return self::DUMMY_FILE_CONTENT;
		}, true );
		self::assertSame( self::DUMMY_FILE_CONTENT, $customsDeclaration->getInvoiceFile() );
		self::assertTrue( $customsDeclaration->hasInvoiceFileContent() );

		$dummyInvoiceFileId = 'dummyInvoiceFileId';
		$customsDeclaration->setInvoiceFileId( $dummyInvoiceFileId );
		self::assertSame( $dummyInvoiceFileId, $customsDeclaration->getInvoiceFileId() );

		$dummyInvoiceIssueDate = CoreHelper::now();
		$customsDeclaration->setInvoiceIssueDate( $dummyInvoiceIssueDate );
		self::assertSame( $dummyInvoiceIssueDate, $customsDeclaration->getInvoiceIssueDate() );

		$dummyInvoiceNumber = 'dummyInvoiceNumber';
		$customsDeclaration->setInvoiceNumber( $dummyInvoiceNumber );
		self::assertSame( $dummyInvoiceNumber, $customsDeclaration->getInvoiceNumber() );

		$dummyMrn = 'dummyMrn';
		$customsDeclaration->setMrn( $dummyMrn );
		self::assertSame( $dummyMrn, $customsDeclaration->getMrn() );

		self::assertIsString( $customsDeclaration->getOrderId() );
	}

}
