<?php

declare( strict_types=1 );

namespace Tests\Module\Order;

use Packetery\Core\Entity\Order;
use Packetery\Module\Order\CustomsDeclarationMetabox;
use Packetery\Module\Order\Metabox;
use Packetery\Module\Order\MetaboxesWrapper;
use Packetery\Module\Order\Repository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use WC_Order;

class MetaboxesWrapperTest extends TestCase {
	private Metabox|MockObject $generalMetabox;
	private CustomsDeclarationMetabox|MockObject $customDeclarationMetabox;
	private Repository|MockObject $orderRepository;

	private function createMetaboxesWrapper(): MetaboxesWrapper {
		$this->generalMetabox           = $this->createMock( Metabox::class );
		$this->customDeclarationMetabox = $this->createMock( CustomsDeclarationMetabox::class );
		$this->orderRepository          = $this->createMock( Repository::class );

		return new MetaboxesWrapper( $this->generalMetabox, $this->customDeclarationMetabox, $this->orderRepository );
	}

	public function testBeforeOrderSaveProcessesOrderOnce(): void {
		$metaboxesWrapper = $this->createMetaboxesWrapper();

		$wcOrder = $this->createMock( WC_Order::class );
		$wcOrder->method( 'get_id' )->willReturn( 100 );

		$order = $this->createMock( Order::class );
		$this->orderRepository
			->method( 'getByIdWithValidCarrier' )
			->with( 100 )
			->willReturn( $order );

		$this->generalMetabox
			->expects( $this->once() )
			->method( 'saveFields' )
			->with( $order, $wcOrder );

		$metaboxesWrapper->beforeOrderSave( $wcOrder );
	}

	public function testBeforeOrderSaveSkipsProcessingWhenOrderNotFound(): void {
		$metaboxesWrapper = $this->createMetaboxesWrapper();

		$wcOrder = $this->createMock( WC_Order::class );
		$wcOrder->method( 'get_id' )->willReturn( 200 );

		$this->orderRepository
			->method( 'getByIdWithValidCarrier' )
			->with( 200 )
			->willReturn( null );

		$this->generalMetabox
			->expects( $this->never() )
			->method( 'saveFields' );

		$metaboxesWrapper->beforeOrderSave( $wcOrder );
	}

	public function testBeforeOrderSavePreventsDuplicateProcessing(): void {
		$metaboxesWrapper = $this->createMetaboxesWrapper();

		$wcOrder1 = $this->createMock( WC_Order::class );
		$wcOrder1->method( 'get_id' )->willReturn( 300 );

		$wcOrder2 = $this->createMock( WC_Order::class );
		$wcOrder2->method( 'get_id' )->willReturn( 300 );

		$order = $this->createMock( Order::class );
		$this->orderRepository
			->method( 'getByIdWithValidCarrier' )
			->with( 300 )
			->willReturn( $order );

		$this->generalMetabox
			->expects( $this->once() )
			->method( 'saveFields' );

		$metaboxesWrapper->beforeOrderSave( $wcOrder1 );
		$metaboxesWrapper->beforeOrderSave( $wcOrder2 );
	}

	public function testBeforeOrderSaveProcessesDifferentOrders(): void {
		$metaboxesWrapper = $this->createMetaboxesWrapper();

		$wcOrder1 = $this->createMock( WC_Order::class );
		$wcOrder1->method( 'get_id' )->willReturn( 400 );

		$wcOrder2 = $this->createMock( WC_Order::class );
		$wcOrder2->method( 'get_id' )->willReturn( 500 );

		$order1 = $this->createMock( Order::class );
		$order2 = $this->createMock( Order::class );

		$this->orderRepository
			->method( 'getByIdWithValidCarrier' )
			->willReturnMap(
				[
					[ 400, $order1 ],
					[ 500, $order2 ],
				]
			);

		$this->generalMetabox
			->expects( $this->exactly( 2 ) )
			->method( 'saveFields' );

		$metaboxesWrapper->beforeOrderSave( $wcOrder1 );
		$metaboxesWrapper->beforeOrderSave( $wcOrder2 );
	}
}
