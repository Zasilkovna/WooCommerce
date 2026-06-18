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

	public function testBuildItemsProducesContiguouslyNumberedStepsWithoutCarrierType(): void {
		$this->wpAdapterMock->method( '__' )->willReturnArgument( 0 );

		$items = $this->dashboardItemBuilder->buildItems();

		$captions   = array_map( static fn( $item ) => $item->getCaption(), $items );
		$sortOrders = array_map( static fn( $item ) => $item->getSortOrder(), $items );

		$this->assertSame(
			[
				'Basic settings of the Packeta plugin',
				'Restrict carriers for products or categories',
				'Carriers update',
				'Carrier settings',
				'Shipping zone settings',
				'Set up shipment status tracking',
				'Set up automatic shipment submission',
				'Currency rates',
				'Age verification 18+',
			],
			$captions
		);
		$this->assertSame( range( 1, count( $items ) ), $sortOrders );
		$this->assertNotContains( 'Carrier type setting', $captions );
		$this->assertNotContains( 'Product settings', $captions );
		$this->assertSame( 'Age verification 18+', $captions[ count( $captions ) - 1 ] );
	}

	public function testIsCurrencyRatesStepFinishedIsFalseWhenFeatureDisabled(): void {
		$this->optionsProviderMock->method( 'isCustomCurrencyRatesEnabled' )->willReturn( false );
		$this->optionsProviderMock->expects( $this->never() )->method( 'getCustomCurrencyRates' );

		$this->assertFalse( $this->invokeIsCurrencyRatesStepFinished() );
	}

	public function testIsCurrencyRatesStepFinishedIsFalseWhenNoUsableRate(): void {
		$this->optionsProviderMock->method( 'isCustomCurrencyRatesEnabled' )->willReturn( true );
		$this->optionsProviderMock->method( 'getCustomCurrencyRates' )->willReturn(
			[
				'EUR' => null,
				'USD' => 0.0,
			]
		);

		$this->assertFalse( $this->invokeIsCurrencyRatesStepFinished() );
	}

	public function testIsCurrencyRatesStepFinishedIsTrueWhenEnabledWithRate(): void {
		$this->optionsProviderMock->method( 'isCustomCurrencyRatesEnabled' )->willReturn( true );
		$this->optionsProviderMock->method( 'getCustomCurrencyRates' )->willReturn(
			[
				'EUR' => null,
				'USD' => 25.3,
			]
		);

		$this->assertTrue( $this->invokeIsCurrencyRatesStepFinished() );
	}

	public function testHasCategoriesWithDisallowedCarriersReflectsTermsResult(): void {
		$this->wpAdapterMock->method( 'getTerms' )->willReturn( [] );
		$this->assertFalse( $this->invokeHasCategoriesWithDisallowedCarriers() );
	}

	public function testHasCategoriesWithDisallowedCarriersIsTrueWhenTermFound(): void {
		$this->wpAdapterMock->method( 'getTerms' )->willReturn( [ 7 ] );
		$this->assertTrue( $this->invokeHasCategoriesWithDisallowedCarriers() );
	}

	public function testHasCategoriesWithDisallowedCarriersIsFalseOnWpError(): void {
		$this->wpAdapterMock->method( 'getTerms' )->willReturn( 'unexpected-non-array' );
		$this->assertFalse( $this->invokeHasCategoriesWithDisallowedCarriers() );
	}

	public function testProductStepDescriptionsAppendCreateProductHintOnlyWhenNoProduct(): void {
		$this->wpAdapterMock->method( '__' )->willReturnArgument( 0 );

		$this->assertSame(
			'Disable specific Packeta carriers for individual products or whole product categories.',
			$this->invokeDescription( 'getDisallowedCarriersDescription', 'https://example.com/edit' )
		);
		$this->assertSame(
			'Mark products intended for adults only (18+). First create at least one product.',
			$this->invokeDescription( 'getAgeVerificationDescription', null )
		);
	}

	private function invokeIsCurrencyRatesStepFinished(): bool {
		return $this->invokePrivateMethod( 'isCurrencyRatesStepFinished' );
	}

	private function invokeHasCategoriesWithDisallowedCarriers(): bool {
		return $this->invokePrivateMethod( 'hasCategoriesWithDisallowedCarriers' );
	}

	private function invokeDescription( string $methodName, ?string $productUrl ): string {
		return $this->invokePrivateMethod( $methodName, $productUrl );
	}

	private function invokeGetCarrierUpdateUrl(): ?string {
		return $this->invokePrivateMethod( 'getCarrierUpdateUrl' );
	}

	private function invokePrivateMethod( string $methodName, mixed ...$args ): mixed {
		$reflection = new ReflectionClass( DashboardItemBuilder::class );
		$method     = $reflection->getMethod( $methodName );
		$method->setAccessible( true );

		return $method->invoke( $this->dashboardItemBuilder, ...$args );
	}
}
