<?php

declare( strict_types=1 );

namespace Tests\Core;

use DateTimeImmutable;
use Packetery\Core\Helper;
use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase {

	private Helper $helper;

	public function __construct( string $name ) {
		parent::__construct( $name );
		$this->helper = new Helper();
	}

	public function testGetTrackingUrl(): void {
		$this->assertIsString( $this->helper->get_tracking_url( 'dummyPacketId' ) );
	}

	public function testGetStringFromDateTime(): void {
		$dummyDateString = '2023-11-17';
		$dummyDate       = $this->helper->getDateTimeFromString( $dummyDateString );

		$this->assertSame(
			$this->helper->getStringFromDateTime( $dummyDate, Helper::MYSQL_DATE_FORMAT ),
			$dummyDateString
		);
	}

	public function testStatic(): void {
		$this->assertSame( 10.222, Helper::simplifyWeight( 10.2222 ) );
		$this->assertNull( Helper::simplifyWeight( null ) );
		$this->assertInstanceOf( DateTimeImmutable::class, Helper::now() );
	}

}
