<?php

declare(strict_types=1);

namespace Tests\Module\Dashboard;

use Packetery\Module\Carrier;
use Packetery\Module\Carrier\CarrierUpdater;
use Packetery\Module\Dashboard\DashboardHelper;
use Packetery\Module\Dashboard\DashboardItemBuilder;
use Packetery\Module\Dashboard\DashboardPage;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Order\PacketSynchronizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class DashboardItemBuilderTest extends TestCase {
	private WpAdapter|MockObject $wpAdapterMock;
	private OptionsProvider|MockObject $optionsProviderMock;
	private CarrierUpdater|MockObject $carrierUpdaterMock;
	private DashboardItemBuilder|MockObject $dashboardItemBuilder;

	protected function setUp(): void {
		$this->wpAdapterMock       = $this->createMock( WpAdapter::class );
		$this->optionsProviderMock = $this->createMock( OptionsProvider::class );
		$this->carrierUpdaterMock  = $this->createMock( CarrierUpdater::class );

		$this->dashboardItemBuilder = new DashboardItemBuilder(
			$this->wpAdapterMock,
			$this->createMock( DashboardHelper::class ),
			$this->optionsProviderMock,
			$this->createMock( Carrier\EntityRepository::class ),
			$this->carrierUpdaterMock,
			$this->createMock( PacketSynchronizer::class ),
		);
	}

	public function testGetCarrierUpdateUrlReturnsNullIfApiPasswordIsNull(): void {
		$this->optionsProviderMock->expects( $this->once() )
									->method( 'get_api_password' )
									->willReturn( null );

		$this->carrierUpdaterMock->expects( $this->never() )
								->method( 'getLastUpdate' );

		$result = $this->invokeGetCarrierUpdateUrl();

		$this->assertNull( $result );
	}

	public function testGetCarrierUpdateUrlReturnsCarrierUrlWhenLastUpdateIsNotNull(): void {
		$this->optionsProviderMock->expects( $this->once() )
									->method( 'get_api_password' )
									->willReturn( 'validApiPassword' );

		$this->carrierUpdaterMock->expects( $this->once() )
								->method( 'getLastUpdate' )
								->willReturn( '2023-10-23' );

		$this->wpAdapterMock->expects( $this->once() )
							->method( 'adminUrl' )
							->with( 'admin.php?page=' . Carrier\OptionsPage::SLUG . '&update_carriers=1' )
							->willReturn( 'https://example.com/admin-options-page-update' );

		$result = $this->invokeGetCarrierUpdateUrl();

		$this->assertEquals( 'https://example.com/admin-options-page-update', $result );
	}

	public function testGetCarrierUpdateUrlReturnsHomeUrlWhenLastUpdateIsNull(): void {
		$this->optionsProviderMock->expects( $this->once() )
									->method( 'get_api_password' )
									->willReturn( 'validApiPassword' );

		$this->carrierUpdaterMock->expects( $this->once() )
								->method( 'getLastUpdate' )
								->willReturn( null );

		$this->wpAdapterMock->expects( $this->once() )
							->method( 'adminUrl' )
							->with( 'admin.php?page=' . DashboardPage::SLUG . '&update_carriers=1' )
							->willReturn( 'https://example.com/admin-dashboard-page-update' );

		$result = $this->invokeGetCarrierUpdateUrl();

		$this->assertEquals( 'https://example.com/admin-dashboard-page-update', $result );
	}

	private function invokeGetCarrierUpdateUrl(): ?string {
		$reflection = new ReflectionClass( DashboardItemBuilder::class );
		$method     = $reflection->getMethod( 'getCarrierUpdateUrl' );
		$method->setAccessible( true );

		return $method->invoke( $this->dashboardItemBuilder );
	}
}
