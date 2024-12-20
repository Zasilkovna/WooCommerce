<?php

declare( strict_types=1 );

namespace Tests\Module\Checkout;

use Packetery\Module\Checkout\CurrencySwitcherService;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\ModuleHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CurrencySwitcherServiceTest extends TestCase {
	private WpAdapter|MockObject $wpAdapter;
	private ModuleHelper|MockObject $moduleHelper;
	private CurrencySwitcherService $currencySwitcherService;

	private function createCurrencySwitcherServiceMock(): void {
		$this->wpAdapter               = $this->createMock( WpAdapter::class );
		$this->moduleHelper            = $this->createMock( ModuleHelper::class );
		$this->currencySwitcherService = new CurrencySwitcherService( $this->wpAdapter, $this->moduleHelper );
	}

	public function testGetConvertedPricePluginActive(): void {
		$this->createCurrencySwitcherServiceMock();

		$inputPrice  = 123.0;
		$outputPrice = 321.0;

		$this->moduleHelper->expects( $this->once() )
							->method( 'isPluginActive' )
							->with( 'woocommerce-currency-switcher/index.php' )
							->willReturn( true );

		$this->wpAdapter->expects( $this->once() )
						->method( 'applyFilters' )
						->with( 'woocs_exchange_value', $inputPrice )
						->willReturn( $outputPrice );

		$this->assertSame( $outputPrice, $this->currencySwitcherService->getConvertedPrice( $inputPrice ) );
	}

	public function testGetConvertedPricePluginNotActive(): void {
		$this->createCurrencySwitcherServiceMock();

		$inputPrice  = 123.0;
		$outputPrice = 321.0;

		$this->moduleHelper->expects( $this->once() )
							->method( 'isPluginActive' )
							->with( 'woocommerce-currency-switcher/index.php' )
							->willReturn( false );

		$this->wpAdapter->expects( $this->once() )
						->method( 'applyFilters' )
						->with( 'packetery_price', $inputPrice )
						->willReturn( $outputPrice );

		$this->assertSame( $outputPrice, $this->currencySwitcherService->getConvertedPrice( $inputPrice ) );
	}
}
