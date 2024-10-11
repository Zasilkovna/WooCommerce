<?php

declare( strict_types=1 );

namespace Tests\Core;

use DateTimeImmutable;
use Packetery\Core\CoreHelper;
use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase {

	private CoreHelper $helper;

	public function __construct( string $name ) {
		parent::__construct( $name );
		$this->helper = new CoreHelper();
	}

	public function testGetTrackingUrl(): void {
		self::assertIsString( $this->helper->get_tracking_url( 'dummyPacketId' ) );
	}

	public function testGetStringFromDateTime(): void {
		$dummyDateString = '2023-11-17';
		$dummyDate       = $this->helper->getDateTimeFromString( $dummyDateString );

		self::assertSame(
			$this->helper->getStringFromDateTime( $dummyDate, CoreHelper::MYSQL_DATE_FORMAT ),
			$dummyDateString
		);
	}

	public function testStatic(): void {
		self::assertSame( 10.222, CoreHelper::simplifyWeight( 10.2222 ) );
		self::assertNull( CoreHelper::simplifyWeight( null ) );
		self::assertInstanceOf( DateTimeImmutable::class, CoreHelper::now() );
		self::assertSame( '4.123', CoreHelper::trimDecimalPlaces( 4.1234566778, 3 ) );
	}

}
