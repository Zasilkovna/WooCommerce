<?php

declare( strict_types=1 );

namespace Tests\Module\Order;

use Packetery\Core\Entity\Order;
use Packetery\Module\Order\CustomsDeclarationMetabox;
use Packetery\Module\Order\Metabox;
use Packetery\Module\Order\MetaboxesWrapper;
use Packetery\Module\Order\Repository;
use PHPUnit\Framework\TestCase;
use WC_Order;

class MetaboxesWrapperTest extends TestCase {
	public function testBeforeOrderSaveProcessesOrderOnce(): void {
		$generalMetabox           = $this->createMock( Metabox::class );
		$customDeclarationMetabox = $this->createMock( CustomsDeclarationMetabox::class );
		$orderRepository          = $this->createMock( Repository::class );
		$metaboxesWrapper         = new MetaboxesWrapper( $generalMetabox, $customDeclarationMetabox, $orderRepository );

		$wcOrder = $this->createMock( WC_Order::class );
		$wcOrder->method( 'get_id' )->willReturn( 100 );

		$order = $this->createMock( Order::class );
		$orderRepository
			->method( 'getByIdWithValidCarrier' )
			->with( 100 )
			->willReturn( $order );

		$generalMetabox
			->expects( $this->once() )
			->method( 'saveFields' )
			->with( $order, $wcOrder );

		$metaboxesWrapper->beforeOrderSave( $wcOrder );
	}

	public function testBeforeOrderSaveSkipsProcessingWhenOrderNotFound(): void {
		$generalMetabox           = $this->createMock( Metabox::class );
		$customDeclarationMetabox = $this->createMock( CustomsDeclarationMetabox::class );
		$orderRepository          = $this->createMock( Repository::class );
		$metaboxesWrapper         = new MetaboxesWrapper( $generalMetabox, $customDeclarationMetabox, $orderRepository );

		$wcOrder = $this->createMock( WC_Order::class );
		$wcOrder->method( 'get_id' )->willReturn( 200 );

		$orderRepository
			->method( 'getByIdWithValidCarrier' )
			->with( 200 )
			->willReturn( null );

		$generalMetabox
			->expects( $this->never() )
			->method( 'saveFields' );

		$metaboxesWrapper->beforeOrderSave( $wcOrder );
	}

	public function testBeforeOrderSavePreventsDuplicateProcessing(): void {
		$generalMetabox           = $this->createMock( Metabox::class );
		$customDeclarationMetabox = $this->createMock( CustomsDeclarationMetabox::class );
		$orderRepository          = $this->createMock( Repository::class );
		$metaboxesWrapper         = new MetaboxesWrapper( $generalMetabox, $customDeclarationMetabox, $orderRepository );

		$wcOrder1 = $this->createMock( WC_Order::class );
		$wcOrder1->method( 'get_id' )->willReturn( 300 );

		$wcOrder2 = $this->createMock( WC_Order::class );
		$wcOrder2->method( 'get_id' )->willReturn( 300 );

		$order = $this->createMock( Order::class );
		$orderRepository
			->method( 'getByIdWithValidCarrier' )
			->with( 300 )
			->willReturn( $order );

		$generalMetabox
			->expects( $this->once() )
			->method( 'saveFields' );

		$metaboxesWrapper->beforeOrderSave( $wcOrder1 );
		$metaboxesWrapper->beforeOrderSave( $wcOrder2 );
	}

	public function testBeforeOrderSaveProcessesDifferentOrders(): void {
		$generalMetabox           = $this->createMock( Metabox::class );
		$customDeclarationMetabox = $this->createMock( CustomsDeclarationMetabox::class );
		$orderRepository          = $this->createMock( Repository::class );
		$metaboxesWrapper         = new MetaboxesWrapper( $generalMetabox, $customDeclarationMetabox, $orderRepository );

		$wcOrder1 = $this->createMock( WC_Order::class );
		$wcOrder1->method( 'get_id' )->willReturn( 400 );

		$wcOrder2 = $this->createMock( WC_Order::class );
		$wcOrder2->method( 'get_id' )->willReturn( 500 );

		$order1 = $this->createMock( Order::class );
		$order2 = $this->createMock( Order::class );

		$orderRepository
			->method( 'getByIdWithValidCarrier' )
			->willReturnMap(
				[
					[ 400, $order1 ],
					[ 500, $order2 ],
				]
			);

		$generalMetabox
			->expects( $this->exactly( 2 ) )
			->method( 'saveFields' );

		$metaboxesWrapper->beforeOrderSave( $wcOrder1 );
		$metaboxesWrapper->beforeOrderSave( $wcOrder2 );
	}
}
