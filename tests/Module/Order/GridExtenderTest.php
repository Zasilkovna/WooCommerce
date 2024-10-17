<?php

declare( strict_types=1 );

namespace Tests\Module\Order;

use Packetery\Core\Entity\Order;
use Packetery\Core\Entity\Size;
use Packetery\Core\CoreHelper;
use Packetery\Core\Validator;
use Packetery\Latte\Engine;
use Packetery\Module\Carrier\CarrierOptionsFactory;
use Packetery\Module\ContextResolver;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Order\GridExtender;
use Packetery\Module\Order\Repository;
use Packetery\Nette\Http\Request;
use PHPUnit\Framework\TestCase;

class GridExtenderTest extends TestCase {

	public static function sizeProvider(): array {
		return [
			[
				'length'       => 300.0,
				'width'        => 200.0,
				'height'       => 100.0,
				'unit'         => OptionsProvider::DIMENSIONS_UNIT_CM,
				'expectedSize' => new Size( 30.0, 20.0, 10.0 )
			],
			[
				'length'       => 300.0,
				'width'        => 200.0,
				'height'       => 100.0,
				'unit'         => 'mm',
				'expectedSize' => new Size( 300.0, 200.0, 100.0 )
			],
			[
				'length'       => null,
				'width'        => null,
				'height'       => null,
				'unit'         => 'mm',
				'expectedSize' => new Size( null, null, null )
			],
			[
				'length'       => null,
				'width'        => null,
				'height'       => null,
				'unit'         => 'cm',
				'expectedSize' => new Size( null, null, null )
			],
		];
	}

	/**
	 * @dataProvider sizeProvider
	 */
	public function testGetSizeInSetDimensionUnit( $length, $width, $height, $unit, $expectedSize ): void {
		$order = $this->createMock( Order::class );
		$order->method( 'getLength' )->willReturn( $length );
		$order->method( 'getWidth' )->willReturn( $width );
		$order->method( 'getHeight' )->willReturn( $height );

		$optionsProvider = $this->createMock( OptionsProvider::class );
		$optionsProvider->method( 'getDimensionsUnit' )->willReturn( $unit );

		$gridExtender = new GridExtender(
			$this->createMock( CoreHelper::class ),
			$this->createMock( Engine::class ),
			$this->createMock( Request::class ),
			$this->createMock( Repository::class ),
			$this->createMock( Validator\Order::class ),
			$this->createMock( ContextResolver::class ),
			$this->createMock( CarrierOptionsFactory::class ),
			$optionsProvider,
		);

		$result = $gridExtender->getSizeInSetDimensionUnit( $order );

		$this->assertEquals( $expectedSize, $result );
	}

}
