<?php

declare( strict_types=1 );

namespace Tests\Module\Order;

use Packetery\Core\Entity\Order;
use Packetery\Module\FormFactory;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Order\Form;
use PHPUnit\Framework\TestCase;

class FormTest extends TestCase {
	public function testGetInvalidFieldsMessageWithInvalidFieldsOnly(): void {
		$wpAdapterMock       = $this->createMock( WpAdapter::class );
		$formFactoryMock     = $this->createMock( FormFactory::class );
		$optionsProviderMock = $this->createMock( OptionsProvider::class );

		$wpAdapterMock->method( '__' )
			->willReturnCallback(
				function ( string $text ): string {
					return $text;
				}
			);

		$orderMock = $this->createMock( Order::class );
		$orderMock->method( 'hasToFillCustomsDeclaration' )->willReturn( false );

		$form = new Form( $formFactoryMock, $optionsProviderMock, $wpAdapterMock );

		$invalidFields   = [ Form::FIELD_VALUE, Form::FIELD_WEIGHT ];
		$expectedMessage = 'Please fill in all required shipment details (value, weight) before submitting.';

		$this->assertEquals(
			$expectedMessage,
			$form->getInvalidFieldsMessageFromValidationResult( $invalidFields, $orderMock )
		);
	}

	public function testGetInvalidFieldsMessageWithInvalidFieldsAndCustomsDeclaration(): void {
		$wpAdapterMock       = $this->createMock( WpAdapter::class );
		$formFactoryMock     = $this->createMock( FormFactory::class );
		$optionsProviderMock = $this->createMock( OptionsProvider::class );

		$wpAdapterMock->method( '__' )
			->willReturnCallback(
				function ( string $text ): string {
					return $text;
				}
			);

		$orderMock = $this->createMock( Order::class );
		$orderMock->method( 'hasToFillCustomsDeclaration' )->willReturn( true );

		$form = new Form( $formFactoryMock, $optionsProviderMock, $wpAdapterMock );

		$invalidFields   = [ Form::FIELD_VALUE, Form::FIELD_WEIGHT ];
		$expectedMessage = 'Please fill in all required shipment details (value, weight) before submitting. Customs declaration has to be filled in order detail.';

		$this->assertEquals(
			$expectedMessage,
			$form->getInvalidFieldsMessageFromValidationResult( $invalidFields, $orderMock )
		);
	}
}
