<?php

declare( strict_types=1 );

namespace Tests\Module\Checkout;

use Packetery\Core\Entity\Carrier;
use Packetery\Module\Api\Internal\CheckoutRouter;
use Packetery\Module\Carrier\CarDeliveryConfig;
use Packetery\Module\Carrier\CarrierOptionsFactory;
use Packetery\Module\Carrier\EntityRepository;
use Packetery\Module\Carrier\OptionPrefixer;
use Packetery\Module\Checkout\CartService;
use Packetery\Module\Checkout\CheckoutService;
use Packetery\Module\Checkout\CheckoutSettings;
use Packetery\Module\Checkout\CheckoutStorage;
use Packetery\Module\Checkout\SessionService;
use Packetery\Module\DiagnosticsLogger\DiagnosticsLogger;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Views\UrlBuilder;
use Packetery\Module\WidgetOptionsBuilder;
use Packetery\Module\WidgetUrlResolver;
use PHPUnit\Framework\TestCase;
use Tests\Core\DummyFactory;
use Tests\Module\MockFactory;
use WC_Cart;

class CheckoutSettingsTest extends TestCase {
	private const PRIME_CALL = 'prime';

	public static function setUpBeforeClass(): void {
		// Plugin::getAppIdentity() reads WC_VERSION and the unit bootstrap does not define it
		if ( ! defined( 'WC_VERSION' ) ) {
			define( 'WC_VERSION', '10.0.0' );
		}
	}

	public function testCreateSettingsPrimesCarrierOptionsBeforeItReadsThem(): void {
		$carriers = [
			DummyFactory::createCarrierCzechPp(),
			DummyFactory::createCarrierGermanHd(),
		];

		$expectedCarrierConfig = [];
		foreach ( $carriers as $carrier ) {
			$expectedCarrierConfig[ OptionPrefixer::getOptionId( $carrier->getId() ) ] = [ 'id' => $carrier->getId() ];
		}
		$expectedOptionIds = array_keys( $expectedCarrierConfig );

		$calls = [];

		$carrierEntityRepository = $this->createMock( EntityRepository::class );
		$carrierEntityRepository
			->method( 'getAllCarriersIncludingNonFeed' )
			->willReturn( $carriers );

		$wpAdapter = MockFactory::createWpAdapter( $this );
		$wpAdapter
			->method( 'getLocale' )
			->willReturn( 'cs_CZ' );
		$wpAdapter
			->expects( $this->once() )
			->method( 'primeOptionCaches' )
			->with( $expectedOptionIds )
			->willReturnCallback(
				static function () use ( &$calls ): void {
					$calls[] = self::PRIME_CALL;
				}
			);

		$widgetOptionsBuilder = $this->createMock( WidgetOptionsBuilder::class );
		$widgetOptionsBuilder
			->method( 'getCarrierForCheckout' )
			->willReturnCallback(
				static function ( Carrier $carrier, string $optionId ) use ( &$calls ): array {
					$calls[] = $optionId;

					return [ 'id' => $carrier->getId() ];
				}
			);

		$wcAdapter = $this->createMock( WcAdapter::class );
		$wcAdapter
			->method( 'cart' )
			->willReturn( $this->createMock( WC_Cart::class ) );

		$checkoutSettings = new CheckoutSettings(
			$carrierEntityRepository,
			$widgetOptionsBuilder,
			$this->createMock( UrlBuilder::class ),
			$this->createMock( CarDeliveryConfig::class ),
			$this->createMock( OptionsProvider::class ),
			$this->createMock( CheckoutRouter::class ),
			$this->createMock( CarrierOptionsFactory::class ),
			$this->createMock( CheckoutStorage::class ),
			$this->createMock( CheckoutService::class ),
			$this->createMock( CartService::class ),
			$this->createMock( SessionService::class ),
			$wpAdapter,
			$wcAdapter,
			$this->createMock( WidgetUrlResolver::class ),
			$this->createMock( DiagnosticsLogger::class )
		);

		$settings = $checkoutSettings->createSettings();

		$this->assertSame( array_merge( [ self::PRIME_CALL ], $expectedOptionIds ), $calls );
		$this->assertSame( $expectedCarrierConfig, $settings['carrierConfig'] );
		$this->assertSame( 'cs', $settings['language'] );
	}
}
