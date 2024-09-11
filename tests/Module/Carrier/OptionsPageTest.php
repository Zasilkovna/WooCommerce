<?php

declare( strict_types=1 );

namespace Tests\Module\Carrier;

use Packetery\Core\PickupPointProvider\CompoundCarrierCollectionFactory;
use Packetery\Core\PickupPointProvider\VendorCollectionFactory;
use Packetery\Latte\Engine;
use Packetery\Module\Carrier\CarDeliveryConfig;
use Packetery\Module\Carrier\CountryListingPage;
use Packetery\Module\Carrier\EntityRepository;
use Packetery\Module\Carrier\OptionsPage;
use Packetery\Module\Carrier\PacketaPickupPointsConfig;
use Packetery\Module\Carrier\WcSettingsConfig;
use Packetery\Module\FormFactory;
use Packetery\Module\MessageManager;
use Packetery\Module\Options\FeatureFlagManager;
use Packetery\Nette\Http\Request;
use PHPUnit\Framework\TestCase;

class OptionsPageTest extends TestCase {

	public function setUp(): void {
		$this->latteEngineMock = $this->createMock(Engine::class);
		$this->carrierRepositoryMock = $this->createMock(EntityRepository::class);
		$this->formFactoryMock = $this->createMock(FormFactory::class);
		$this->request = $this->createMock(Request::class);
		$this->countryListingPageMock = $this->createMock(CountryListingPage::class);
		$this->messageManagerMock = $this->createMock( MessageManager::class);
		$this->featureFlagManagerMock = $this->createMock(FeatureFlagManager::class);
		$this->carDeliveryConfigMock = $this->createMock(CarDeliveryConfig::class);
		$this->wcSettingsConfigMock = $this->createMock(WcSettingsConfig::class);

		$this->compoundCarrierFactory = new CompoundCarrierCollectionFactory();
		$this->vendorCollectionFactory = new VendorCollectionFactory();
		$this->featureFlagMock = $this->createMock(FeatureFlagManager::class);

		$this->packetaPickupPointsConfig = new PacketaPickupPointsConfig(
			$this->compoundCarrierFactory,
			$this->vendorCollectionFactory,
			$this->featureFlagMock
		);

			$this->optionsPage = new OptionsPage(
			$this->latteEngineMock,
			$this->carrierRepositoryMock,
			$this->formFactoryMock,
			$this->request,
			$this->countryListingPageMock,
			$this->messageManagerMock,
			$this->packetaPickupPointsConfig,
			$this->featureFlagManagerMock,
			$this->carDeliveryConfigMock,
			$this->wcSettingsConfigMock,
		);
	}

	public function testAvailableVendorsAreLowerThanRequiredMinimum(): void {
		$this->featureFlagMock->method('isSplitActive')->willReturn(true);
		self::assertTrue($this->optionsPage->isCountAvailableVendorsLowByCarrierId('zpointcz'));
		self::assertTrue($this->optionsPage->isCountAvailableVendorsLowByCarrierId('zpointsk'));
		self::assertTrue($this->optionsPage->isCountAvailableVendorsLowByCarrierId('zpointhu'));
		self::assertTrue($this->optionsPage->isCountAvailableVendorsLowByCarrierId('zpointro'));
	}

}
