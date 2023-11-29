<?php

declare( strict_types=1 );

namespace Tests;

use Packetery\Core\Api\Rest\PickupPointValidateRequest;
use Packetery\Core\Entity\Address;
use Packetery\Core\Entity\Carrier;
use Packetery\Core\Entity\CustomsDeclaration;
use Packetery\Core\Entity\CustomsDeclarationItem;
use Packetery\Core\Entity\Order;
use Packetery\Core\Entity\PickupPoint;
use Packetery\Core\Entity\Size;
use Packetery\Core\Helper;
use Packetery\Core\PickupPointProvider\VendorProvider;

class DummyFactory {

	public static function createAddress(): Address {
		return new Address( 'Dummy street', 'Dummy city', '123 45' );
	}

	public static function createInvalidAddress(): Address {
		return new Address( null, 'Dummy city', '123 45' );
	}

	public static function createSize(): Size {
		return new Size( 300.0, 200.0, 100.0 );
	}

	public static function createOrderCzPp(): Order {
		$order = new Order( 'dummyNumber123', self::createCarrierCzechPp() );
		$order->setName( 'Customer name' );
		$order->setValue( 123.5 );
		$order->setEshop( 'Sender label' );
		$order->setWeight( 1.25 );

		return $order;
	}

	public static function createOrderCzHdIncomplete(): Order {
		$order = new Order( 'dummyNumber123', self::createCarrierCzechHdRequiresSize() );
		$order->setName( 'Customer name' );
		$order->setValue( 123.5 );
		$order->setEshop( 'Sender label' );
		$order->setWeight( 1.25 );

		return $order;
	}

	public static function createCarrierCzechPp(): Carrier {
		return new Carrier(
			'zpoint-cz',
			'zpoint-cz',
			true,
			false,
			false,
			false,
			true,
			true,
			false,
			true,
			'cz',
			'CZK',
			5.0,
			false,
			true,
		);
	}

	public static function createCarrierCzechHdRequiresSize(): Carrier {
		return new Carrier(
			'106',
			'hd-cz',
			false,
			false,
			false,
			false,
			true,
			true,
			true,
			true,
			'cz',
			'CZK',
			5.0,
			false,
			true,
		);
	}

	public static function createPickupPoint(): PickupPoint {
		return new PickupPoint(
			'123456',
			'Dummy PP',
			'Dummy city',
			'123 45',
			'Dummy street',
			null,
		);
	}

	public static function createVendor(): VendorProvider {
		return new VendorProvider(
			'czzpoint',
			'cz',
			true,
			true,
			'CZK',
			true,
			Carrier::VENDOR_GROUP_ZPOINT,
		);
	}

	public static function createCustomsDeclaration(): CustomsDeclaration {
		$customsDeclaration = new CustomsDeclaration(
			'dummyOrderId123',
			'dummyEad',
			1234.5,
			'dummyInvoiceNumber123',
			Helper::now(),
		);

		$customsDeclaration->setItems([self::createCustomsDeclarationItem()]);

		return $customsDeclaration;
	}

	public static function createCustomsDeclarationItem() {
		return new CustomsDeclarationItem(
			'dummyId123',
			'Dummy customs code',
			1234.5,
			'Dummy product name',
			1,
			'de',
			1.25,
		);
	}

	public static function getEmptyPickupPointValidateRequest(): PickupPointValidateRequest {
		return new PickupPointValidateRequest(
			'dummyPointId',
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
		);
	}

}
