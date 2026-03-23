<?php

declare( strict_types=1 );

namespace Tests\Module\Carrier;

use Packetery\Latte\Engine;
use Packetery\Module\Carrier\CarDeliveryConfig;
use Packetery\Module\Carrier\CountryListingPage;
use Packetery\Module\Carrier\EntityRepository;
use Packetery\Module\Carrier\OptionsPage;
use Packetery\Module\Carrier\ShippingClassPage;
use Packetery\Module\Forms\CarrierFormFactory;
use Packetery\Module\Forms\ShippingClassFormFactory;
use Packetery\Module\Forms\ShippingFormHelper;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\MessageManager;
use Packetery\Module\ModuleHelper;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Views\UrlBuilder;
use Packetery\Nette\Http\Request;
use PHPUnit\Framework\TestCase;

class OptionsPageTest extends TestCase {
	public function testIsAvailableVendorsCountLowByCarrierId(): void {
		$latteEngineMock        = $this->createMock( Engine::class );
		$carrierRepositoryMock  = $this->createMock( EntityRepository::class );
		$request                = $this->createMock( Request::class );
		$countryListingPageMock = $this->createMock( CountryListingPage::class );
		$messageManagerMock     = $this->createMock( MessageManager::class );
		$carDeliveryConfigMock  = $this->createMock( CarDeliveryConfig::class );
		$carrierFormFactory     = $this->createMock( CarrierFormFactory::class );
		$carrierFormFactory
			->method( 'getAvailableVendors' )
			->willReturn( [ 'foo' ] );
		$carrierFormFactory
			->method( 'isAvailableVendorsCountLowerThanRequiredMinimum' )
			->willReturn( true );

		$optionsPage = new OptionsPage(
			$latteEngineMock,
			$carrierRepositoryMock,
			$request,
			$countryListingPageMock,
			$messageManagerMock,
			$carDeliveryConfigMock,
			$this->createMock( ModuleHelper::class ),
			$this->createMock( UrlBuilder::class ),
			$this->createMock( WpAdapter::class ),
			$this->createMock( WcAdapter::class ),
			$this->createMock( OptionsProvider::class ),
			$this->createMock( ShippingClassPage::class ),
			$this->createMock( ShippingFormHelper::class ),
			$carrierFormFactory,
			$this->createMock( ShippingClassFormFactory::class ),
		);
		self::assertTrue( $optionsPage->isAvailableVendorsCountLowByCarrierId( 'zpointcz' ) );
		self::assertTrue( $optionsPage->isAvailableVendorsCountLowByCarrierId( 'zpointsk' ) );
		self::assertTrue( $optionsPage->isAvailableVendorsCountLowByCarrierId( 'zpointhu' ) );
		self::assertTrue( $optionsPage->isAvailableVendorsCountLowByCarrierId( 'zpointro' ) );
	}
}
