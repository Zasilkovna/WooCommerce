<?php

declare( strict_types=1 );

namespace Tests\Module\Carrier;

use Packetery\Core\PickupPointProvider\CompoundCarrierCollectionFactory;
use Packetery\Core\PickupPointProvider\VendorCollectionFactory;
use Packetery\Latte\Engine;
use Packetery\Module\Carrier\CarDeliveryConfig;
use Packetery\Module\Carrier\CarrierOptionsFactory;
use Packetery\Module\Carrier\CountryListingPage;
use Packetery\Module\Carrier\EntityRepository;
use Packetery\Module\Carrier\OptionsPage;
use Packetery\Module\Carrier\PacketaPickupPointsConfig;
use Packetery\Module\FormFactory;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\MessageManager;
use Packetery\Module\ModuleHelper;
use Packetery\Module\Views\UrlBuilder;
use Packetery\Nette\Http\Request;
use PHPUnit\Framework\TestCase;

class OptionsPageTest extends TestCase {
	public function testIsAvailableVendorsCountLowByCarrierId(): void {
		$latteEngineMock        = $this->createMock( Engine::class );
		$carrierRepositoryMock  = $this->createMock( EntityRepository::class );
		$formFactoryMock        = $this->createMock( FormFactory::class );
		$request                = $this->createMock( Request::class );
		$countryListingPageMock = $this->createMock( CountryListingPage::class );
		$messageManagerMock     = $this->createMock( MessageManager::class );
		$carDeliveryConfigMock  = $this->createMock( CarDeliveryConfig::class );

		$compoundCarrierFactory  = new CompoundCarrierCollectionFactory();
		$vendorCollectionFactory = new VendorCollectionFactory();

		$packetaPickupPointsConfig = new PacketaPickupPointsConfig(
			$compoundCarrierFactory,
			$vendorCollectionFactory,
		);

		$optionsPage = new OptionsPage(
			$latteEngineMock,
			$carrierRepositoryMock,
			$formFactoryMock,
			$request,
			$countryListingPageMock,
			$messageManagerMock,
			$packetaPickupPointsConfig,
			$carDeliveryConfigMock,
			$this->createMock( CarrierOptionsFactory::class ),
			$this->createMock( ModuleHelper::class ),
			$this->createMock( UrlBuilder::class ),
			$this->createMock( WpAdapter::class ),
		);
		self::assertTrue( $optionsPage->isAvailableVendorsCountLowByCarrierId( 'zpointcz' ) );
		self::assertTrue( $optionsPage->isAvailableVendorsCountLowByCarrierId( 'zpointsk' ) );
		self::assertTrue( $optionsPage->isAvailableVendorsCountLowByCarrierId( 'zpointhu' ) );
		self::assertTrue( $optionsPage->isAvailableVendorsCountLowByCarrierId( 'zpointro' ) );
	}
}
