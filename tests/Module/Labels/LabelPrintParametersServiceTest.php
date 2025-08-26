<?php

namespace Tests\Module\Labels;

use Packetery\Core\Entity\Order;
use Packetery\Module\FormFactory;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Labels\LabelPrintParametersService;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Order\Repository;
use Packetery\Nette\Forms\Form;
use Packetery\Nette\Http\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LabelPrintParametersServiceTest extends TestCase {
	private WpAdapter|MockObject $wpAdapterMock;
	private OptionsProvider|MockObject $optionsProviderMock;
	private FormFactory|MockObject $formFactoryMock;
	private Request|MockObject $httpRequestMock;
	private Repository|MockObject $orderRepositoryMock;
	private LabelPrintParametersService $labelPrintParametersService;

	protected function createLabelPrintParametersServiceMock(): void {
		$this->wpAdapterMock       = $this->createMock( WpAdapter::class );
		$this->optionsProviderMock = $this->createMock( OptionsProvider::class );
		$this->formFactoryMock     = $this->createMock( FormFactory::class );
		$this->httpRequestMock     = $this->createMock( Request::class );
		$this->orderRepositoryMock = $this->createMock( Repository::class );

		$this->labelPrintParametersService = new LabelPrintParametersService(
			$this->wpAdapterMock,
			$this->optionsProviderMock,
			$this->formFactoryMock,
			$this->httpRequestMock,
			$this->orderRepositoryMock
		);
	}

	public function testGetOffsetWithMaxOffsetZero(): void {
		$this->createLabelPrintParametersServiceMock();

		$this->optionsProviderMock->expects( $this->once() )
			->method( 'getLabelMaxOffset' )
			->willReturn( 0 );
		$this->assertSame( 0, $this->labelPrintParametersService->getOffset() );
	}

	public function testGetOffsetWhenOffsetQueryExists(): void {
		$this->createLabelPrintParametersServiceMock();
		$offsetQueryValue = '3';

		$this->optionsProviderMock->expects( $this->once() )
			->method( 'getLabelMaxOffset' )
			->willReturn( 5 );

		$this->httpRequestMock->method( 'getQuery' )
			->willReturn( $offsetQueryValue );

		$this->wpAdapterMock->method( '__' )
			->willReturn( 'foo %s bar' );

		$this->assertSame( (int) $offsetQueryValue, $this->labelPrintParametersService->getOffset() );
	}

	public function testGetOffsetWhenFormIsSubmitted(): void {
		$this->createLabelPrintParametersServiceMock();
		$offset          = '2';
		$submittedOffset = [ 'offset' => $offset ];

		$mockForm = $this->createMock( Form::class );
		$mockForm->expects( $this->once() )
			->method( 'isSubmitted' )
			->willReturn( true );
		$mockForm->expects( $this->once() )
			->method( 'getValues' )
			->with( 'array' )
			->willReturn( $submittedOffset );

		$this->optionsProviderMock->method( 'getLabelMaxOffset' )
			->willReturn( 5 );
		$this->formFactoryMock->expects( $this->once() )
			->method( 'create' )
			->willReturn( $mockForm );
		$this->httpRequestMock->method( 'getQuery' )
			->willReturn( null );
		$this->wpAdapterMock->method( '__' )
			->willReturn( 'foo %s bar' );

		$this->assertSame( (int) $offset, $this->labelPrintParametersService->getOffset() );
	}

	public function testGetOffsetWhenNothingIsSet(): void {
		$this->createLabelPrintParametersServiceMock();

		$mockForm = $this->createMock( Form::class );
		$mockForm->expects( $this->once() )
			->method( 'isSubmitted' )
			->willReturn( false );

		$this->optionsProviderMock->expects( $this->once() )
			->method( 'getLabelMaxOffset' )
			->willReturn( 5 );
		$this->formFactoryMock->expects( $this->once() )
			->method( 'create' )
			->willReturn( $mockForm );
		$this->httpRequestMock->method( 'getQuery' )
			->willReturn( null );
		$this->wpAdapterMock->method( '__' )
			->willReturn( 'foo %s bar' );

		$this->assertNull( $this->labelPrintParametersService->getOffset() );
	}

	/**
	 * Tests that external carrier packet IDs are removed when neither carrier labels nor fallback to Packeta label is allowed.
	 */
	public function testRemoveExternalCarrierPacketIdsPacketaOnly(): void {
		$this->createLabelPrintParametersServiceMock();

		$packetIds = [
			1 => 'packet_1',
			2 => 'packet_2',
		];

		$orderMock1 = $this->createMock( Order::class );
		$orderMock1->method( 'isExternalCarrier' )->willReturn( false );

		$orderMock2 = $this->createMock( Order::class );
		$orderMock2->method( 'isExternalCarrier' )->willReturn( true );

		$this->orderRepositoryMock
			->method( 'getByIdWithValidCarrier' )
			->will(
				$this->returnValueMap(
					[
						[ 1, $orderMock1 ],
						[ 2, $orderMock2 ],
					]
				)
			);

		$result = $this->labelPrintParametersService->removeExternalCarrierPacketIds( $packetIds, false, false );

		$this->assertEquals( [ 1 => 'packet_1' ], $result );
	}

	/**
	 * Tests that packet IDs are not removed when carrier labels are enabled.
	 */
	public function testRemoveExternalCarrierPacketIdsKeepAll(): void {
		$this->createLabelPrintParametersServiceMock();

		$packetIds = [
			1 => 'packet_1',
			2 => 'packet_2',
		];

		$this->orderRepositoryMock->expects( $this->never() )->method( 'getByIdWithValidCarrier' );

		$result = $this->labelPrintParametersService->removeExternalCarrierPacketIds( $packetIds, true, false );

		$this->assertEquals( $packetIds, $result );
	}

	public function testGetLabelFormatByOrderWithExternalCarrier(): void {
		$this->createLabelPrintParametersServiceMock();

		$orderMock = $this->createMock( Order::class );
		$orderMock->expects( $this->once() )
			->method( 'isExternalCarrier' )
			->willReturn( true );

		$expectedFormat = 'CARRIER_FORMAT';
		$this->optionsProviderMock->expects( $this->once() )
			->method( 'get_carrier_label_format' )
			->willReturn( $expectedFormat );

		$this->assertSame(
			$expectedFormat,
			$this->labelPrintParametersService->getLabelFormatByOrder( $orderMock )
		);
	}

	public function testGetLabelFormatByOrderWithPacketaInternalCarrier(): void {
		$this->createLabelPrintParametersServiceMock();

		$orderMock = $this->createMock( Order::class );
		$orderMock->expects( $this->once() )
			->method( 'isExternalCarrier' )
			->willReturn( false );

		$expectedFormat = 'PACKETA_FORMAT';
		$this->optionsProviderMock->expects( $this->once() )
			->method( 'get_packeta_label_format' )
			->willReturn( $expectedFormat );

		$this->assertSame(
			$expectedFormat,
			$this->labelPrintParametersService->getLabelFormatByOrder( $orderMock )
		);
	}
}
