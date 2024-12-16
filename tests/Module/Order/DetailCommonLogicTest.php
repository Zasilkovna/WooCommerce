<?php

declare( strict_types=1 );

namespace Module\Order;

use Packetery\Core\Entity\PickupPoint;
use Packetery\Module\ContextResolver;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Order\DetailCommonLogic;
use Packetery\Module\Order\Repository;
use Packetery\Nette\Http\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tests\Core\DummyFactory;

class DetailCommonLogicTest extends TestCase {

	private WcAdapter|MockObject $wcAdapter;
	private OptionsProvider|MockObject $optionsProvider;

	public function createDetailCommonLogic(): DetailCommonLogic {

		$this->wcAdapter       = $this->createMock( WcAdapter::class );
		$this->optionsProvider = $this->createMock( OptionsProvider::class );

		return new DetailCommonLogic(
			$this->createMock( ContextResolver::class ),
			$this->createMock( Request::class ),
			$this->createMock( Repository::class ),
			$this->optionsProvider,
			$this->wcAdapter,
		);
	}

	public static function shouldDisplayPickupPointInfoProvider(): array {
		return [
			[
				'expected'                 => true,
				'replaceShippingAddressWithPickupPointAddress' => true,
				'shipToBillingAddressOnly' => true,
			],
			[
				'expected'                 => false,
				'replaceShippingAddressWithPickupPointAddress' => true,
				'shipToBillingAddressOnly' => false,
			],
			[
				'expected'                 => true,
				'replaceShippingAddressWithPickupPointAddress' => false,
				'shipToBillingAddressOnly' => true,
			],
			[
				'expected'                 => true,
				'replaceShippingAddressWithPickupPointAddress' => false,
				'shipToBillingAddressOnly' => false,
			],
		];
	}

	/**
	 * @dataProvider shouldDisplayPickupPointInfoProvider
	 */
	public function testShouldDisplayPickupPointInfo(
		bool $expected,
		bool $replaceShippingAddressWithPickupPointAddress,
		bool $shipToBillingAddressOnly,
	): void {
		$detailCommonLogic = $this->createDetailCommonLogic();
		$this->optionsProvider->method( 'replaceShippingAddressWithPickupPointAddress' )->willReturn( $replaceShippingAddressWithPickupPointAddress );
		$this->wcAdapter->method( 'shipToBillingAddressOnly' )->willReturn( $shipToBillingAddressOnly );

		$this->assertSame( $expected, $detailCommonLogic->shouldDisplayPickupPointInfo() );
	}

	public static function shouldHidePacketaInfoProvider(): array {
		return [
			[
				'expected'                 => true,
				'replaceShippingAddressWithPickupPointAddress' => true,
				'shipToBillingAddressOnly' => true,
				'orderPickupPoint'         => null,
				'orderExported'            => false,
			],
			[
				'expected'                 => true,
				'replaceShippingAddressWithPickupPointAddress' => true,
				'shipToBillingAddressOnly' => false,
				'orderPickupPoint'         => null,
				'orderExported'            => false,
			],
			[
				'expected'                 => true,
				'replaceShippingAddressWithPickupPointAddress' => false,
				'shipToBillingAddressOnly' => true,
				'orderPickupPoint'         => null,
				'orderExported'            => false,
			],
			[
				'expected'                 => true,
				'replaceShippingAddressWithPickupPointAddress' => false,
				'shipToBillingAddressOnly' => false,
				'orderPickupPoint'         => null,
				'orderExported'            => false,
			],
			[
				'expected'                 => false,
				'replaceShippingAddressWithPickupPointAddress' => true,
				'shipToBillingAddressOnly' => true,
				'orderPickupPoint'         => null,
				'orderExported'            => true,
			],
			[
				'expected'                 => false,
				'replaceShippingAddressWithPickupPointAddress' => true,
				'shipToBillingAddressOnly' => false,
				'orderPickupPoint'         => null,
				'orderExported'            => true,
			],
			[
				'expected'                 => false,
				'replaceShippingAddressWithPickupPointAddress' => false,
				'shipToBillingAddressOnly' => true,
				'orderPickupPoint'         => null,
				'orderExported'            => true,
			],
			[
				'expected'                 => false,
				'replaceShippingAddressWithPickupPointAddress' => false,
				'shipToBillingAddressOnly' => false,
				'orderPickupPoint'         => null,
				'orderExported'            => true,
			],
			[
				'expected'                 => false,
				'replaceShippingAddressWithPickupPointAddress' => true,
				'shipToBillingAddressOnly' => true,
				'orderPickupPoint'         => new PickupPoint(),
				'orderExported'            => false,
			],
			[
				'expected'                 => true,
				'replaceShippingAddressWithPickupPointAddress' => true,
				'shipToBillingAddressOnly' => false,
				'orderPickupPoint'         => new PickupPoint(),
				'orderExported'            => false,
			],
			[
				'expected'                 => false,
				'replaceShippingAddressWithPickupPointAddress' => false,
				'shipToBillingAddressOnly' => true,
				'orderPickupPoint'         => new PickupPoint(),
				'orderExported'            => false,
			],
			[
				'expected'                 => false,
				'replaceShippingAddressWithPickupPointAddress' => false,
				'shipToBillingAddressOnly' => false,
				'orderPickupPoint'         => new PickupPoint(),
				'orderExported'            => false,
			],
			[
				'expected'                 => false,
				'replaceShippingAddressWithPickupPointAddress' => true,
				'shipToBillingAddressOnly' => true,
				'orderPickupPoint'         => new PickupPoint(),
				'orderExported'            => true,
			],
			[
				'expected'                 => false,
				'replaceShippingAddressWithPickupPointAddress' => true,
				'shipToBillingAddressOnly' => false,
				'orderPickupPoint'         => new PickupPoint(),
				'orderExported'            => true,
			],
			[
				'expected'                 => false,
				'replaceShippingAddressWithPickupPointAddress' => false,
				'shipToBillingAddressOnly' => true,
				'orderPickupPoint'         => new PickupPoint(),
				'orderExported'            => true,
			],
			[
				'expected'                 => false,
				'replaceShippingAddressWithPickupPointAddress' => false,
				'shipToBillingAddressOnly' => false,
				'orderPickupPoint'         => new PickupPoint(),
				'orderExported'            => true,
			],

		];
	}

	/**
	 * @dataProvider shouldHidePacketaInfoProvider
	 */
	public function testShouldHidePacketaInfo(
		bool $expected,
		bool $replaceShippingAddressWithPickupPointAddress,
		bool $shipToBillingAddressOnly,
		?PickupPoint $orderPickupPoint,
		bool $orderExported,
	): void {
		$detailCommonLogic = $this->createDetailCommonLogic();
		$this->optionsProvider->method( 'replaceShippingAddressWithPickupPointAddress' )->willReturn( $replaceShippingAddressWithPickupPointAddress );
		$this->wcAdapter->method( 'shipToBillingAddressOnly' )->willReturn( $shipToBillingAddressOnly );

		$dummyOrder = DummyFactory::createOrderCzPp();
		if ( $orderPickupPoint !== null ) {
			$dummyOrder->setPickupPoint( $orderPickupPoint );
		}
		$dummyOrder->setIsExported( $orderExported );
		$this->assertSame( $expected, $detailCommonLogic->shouldHidePacketaInfo( $dummyOrder ) );
	}
}
