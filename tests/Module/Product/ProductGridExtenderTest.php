<?php

declare( strict_types=1 );

namespace Tests\Module\Product;

use Packetery\Latte\Engine;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Log\ArgumentTypeErrorLogger;
use Packetery\Module\Product\Entity;
use Packetery\Module\Product\ProductEntityFactory;
use Packetery\Module\Product\ProductGridExtender;
use Packetery\Module\WpdbAdapter;
use Packetery\Nette\Http\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductGridExtenderTest extends TestCase {

	private WpAdapter&MockObject $wpAdapter;
	private Request&MockObject $httpRequest;
	private ProductEntityFactory&MockObject $productEntityFactory;
	private ArgumentTypeErrorLogger&MockObject $argumentTypeErrorLogger;
	private WpdbAdapter&MockObject $wpdbAdapter;
	private Engine&MockObject $latteEngine;

	private function createProductGridExtender(): ProductGridExtender {
		$this->wpAdapter               = $this->createMock( WpAdapter::class );
		$this->httpRequest             = $this->createMock( Request::class );
		$this->productEntityFactory    = $this->createMock( ProductEntityFactory::class );
		$this->argumentTypeErrorLogger = $this->createMock( ArgumentTypeErrorLogger::class );
		$this->wpdbAdapter             = $this->createMock( WpdbAdapter::class );
		$this->latteEngine             = $this->createMock( Engine::class );

		$this->wpAdapter
			->method( '__' )
			->willReturnCallback(
				static function ( string $text ): string {
					return $text;
				}
			);

		return new ProductGridExtender(
			$this->wpAdapter,
			$this->httpRequest,
			$this->productEntityFactory,
			$this->argumentTypeErrorLogger,
			$this->wpdbAdapter,
			$this->latteEngine
		);
	}

	public function testFillCustomProductListColumns(): void {
		$gridExtender = $this->createProductGridExtender();
		$product      = $this->createMock( Entity::class );
		$product->method( 'isAgeVerificationRequired' )->willReturn( true );
		$product->method( 'getDisallowedShippingRateIds' )->willReturn( [ 'rate1' ] );

		$this->productEntityFactory
			->method( 'fromPostId' )
			->willReturn( $product );

		$this->latteEngine
			->expects( $this->exactly( 2 ) )
			->method( 'render' );

		$gridExtender->fillCustomProductListColumns( ProductGridExtender::COLUMN_AGE_VERIFICATION, 123 );
		$gridExtender->fillCustomProductListColumns( ProductGridExtender::COLUMN_SHIPPING_RESTRICTIONS, 123 );
	}

	public function testAddProductFilters(): void {
		$gridExtender = $this->createProductGridExtender();
		$this->wpAdapter
			->method( 'applyFilters' )
			->willReturn(
				[
					ProductGridExtender::FILTER_AGE_VERIFICATION => true,
					ProductGridExtender::FILTER_SHIPPING_RESTRICTIONS => false,
				]
			);

		$result = $gridExtender->addProductFilters( [] );

		$this->assertArrayHasKey( ProductGridExtender::FILTER_AGE_VERIFICATION, $result );
		$this->assertArrayNotHasKey( ProductGridExtender::FILTER_SHIPPING_RESTRICTIONS, $result );
		$this->assertIsCallable( $result[ ProductGridExtender::FILTER_AGE_VERIFICATION ] );
	}
}
