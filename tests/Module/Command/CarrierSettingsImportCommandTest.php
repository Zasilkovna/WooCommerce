<?php

declare(strict_types=1);

namespace Tests\Module\Command;

use Packetery\Core\Entity\Carrier;
use Packetery\Module\Carrier\Downloader;
use Packetery\Module\Carrier\EntityRepository;
use Packetery\Module\Carrier\OptionsPage;
use Packetery\Module\Command\CarrierSettingsImportCommand;
use Packetery\Module\Forms\FormService;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Nette\Forms\Form;
use PHPUnit\Framework\TestCase;

class CarrierSettingsImportCommandTest extends TestCase {

	/**
	 * @var WpAdapter|MockObject
	 */
	private $wpAdapter;

	/**
	 * @var OptionsPage|MockObject
	 */
	private $optionsPage;

	/**
	 * @var FormService|MockObject
	 */
	private $formService;

	/**
	 * @var Downloader|MockObject
	 */
	private $carrierDownloader;

	/**
	 * @var EntityRepository|MockObject
	 */
	private $carrierEntityRepository;

	protected function setUp(): void {
		$this->wpAdapter               = $this->createMock( WpAdapter::class );
		$this->optionsPage             = $this->createMock( OptionsPage::class );
		$this->formService             = $this->createMock( FormService::class );
		$this->carrierDownloader       = $this->createMock( Downloader::class );
		$this->carrierEntityRepository = $this->createMock( EntityRepository::class );
	}

	public function testSuccessfulImport(): void {
		$this->carrierDownloader->expects( $this->once() )
			->method( 'run' )
			->willReturn( [ 'Success', 'success' ] );

		$form = $this->createMock( Form::class );
		$form->expects( $this->exactly( 2 ) )
			->method( 'setValues' );
		$form->expects( $this->exactly( 2 ) )
			->method( 'isValid' )
			->willReturn( true );
		$form->expects( $this->exactly( 2 ) )
			->method( 'getValues' )
			->with( 'array' )
			->willReturn( [ 'some' => 'values' ] );

		$this->optionsPage->expects( $this->exactly( 2 ) )
			->method( 'createForm' )
			->willReturn( $form );
		$this->optionsPage->expects( $this->exactly( 2 ) )
			->method( 'updateOptionsWithArray' );
		$this->carrierEntityRepository->expects( $this->exactly( 2 ) )
			->method( 'getAnyById' )
			->willReturn(
				new Carrier(
					'1',
					'Test Carrier',
					true,
					true,
					false,
					false,
					true,
					true,
					false,
					true,
					'cz',
					'CZK',
					10.0,
					true,
					false,
					false
				)
			);
		$this->wpAdapter->expects( $this->once() )
			->method( 'cliSuccess' )
			->with( 'Carrier settings were imported.' );

		$command = new CarrierSettingsImportCommand(
			__DIR__ . '/config/valid-config.php',
			$this->wpAdapter,
			$this->optionsPage,
			$this->formService,
			$this->carrierDownloader,
			$this->carrierEntityRepository
		);

		$command->__invoke();
	}

	public function testSkipsNonExistentCarriers(): void {
		$this->carrierDownloader->expects( $this->once() )
			->method( 'run' )
			->willReturn( [ 'Success', 'success' ] );

		$form = $this->createMock( Form::class );
		$form->expects( $this->once() )
			->method( 'setValues' );
		$form->expects( $this->once() )
			->method( 'isValid' )
			->willReturn( true );
		$form->expects( $this->once() )
			->method( 'getValues' )
			->with( 'array' )
			->willReturn( [ 'some' => 'values' ] );

		$this->optionsPage->expects( $this->once() )
			->method( 'createForm' )
			->willReturn( $form );
		$this->optionsPage->expects( $this->once() )
			->method( 'updateOptionsWithArray' );
		$this->carrierEntityRepository->expects( $this->exactly( 2 ) )
			->method( 'getAnyById' )
			->willReturnCallback(
				function ( string $id ) {
					return $id === '1' ? new Carrier(
						'1',
						'Test Carrier',
						true,
						true,
						false,
						false,
						true,
						true,
						false,
						true,
						'cz',
						'CZK',
						10.0,
						true,
						false,
						false
					) : null;
				}
			);
		$this->wpAdapter->expects( $this->once() )
			->method( 'cliSuccess' )
			->with( 'Carrier settings were imported.' );

		$command = new CarrierSettingsImportCommand(
			__DIR__ . '/config/valid-config.php',
			$this->wpAdapter,
			$this->optionsPage,
			$this->formService,
			$this->carrierDownloader,
			$this->carrierEntityRepository
		);

		$command->__invoke();
	}

	public function testCarrierDownloadFailure(): void {
		$this->carrierDownloader->expects( $this->once() )
			->method( 'run' )
			->willReturn( [ 'Download failed', 'error' ] );

		$this->wpAdapter->expects( $this->once() )
			->method( 'cliError' )
			->with( 'Download failed' );

		$command = new CarrierSettingsImportCommand(
			__DIR__ . '/config/valid-config.php',
			$this->wpAdapter,
			$this->optionsPage,
			$this->formService,
			$this->carrierDownloader,
			$this->carrierEntityRepository
		);

		$command->__invoke();
	}

	public function testMissingConfigFile(): void {
		$this->wpAdapter->expects( $this->once() )
			->method( 'cliError' )
			->with( 'Config file does not exist.' )
			->willThrowException( new \RuntimeException( 'Config file does not exist.' ) );

		$command = new CarrierSettingsImportCommand(
			__DIR__ . '/config/non-existent-file.php',
			$this->wpAdapter,
			$this->optionsPage,
			$this->formService,
			$this->carrierDownloader,
			$this->carrierEntityRepository
		);

		$this->expectException( \RuntimeException::class );
		$command->__invoke();
	}

	public function testInvalidConfigStructure(): void {
		$this->carrierDownloader->expects( $this->once() )
			->method( 'run' )
			->willReturn( [ 'Success', 'success' ] );

		$this->wpAdapter->expects( $this->once() )
			->method( 'cliError' )
			->with( 'Configuration must contain carriers and global_settings' );

		$command = new CarrierSettingsImportCommand(
			__DIR__ . '/config/invalid-structure.php',
			$this->wpAdapter,
			$this->optionsPage,
			$this->formService,
			$this->carrierDownloader,
			$this->carrierEntityRepository
		);

		$command->__invoke();
	}

	public function testInvalidFormData(): void {
		$this->carrierDownloader->expects( $this->once() )
			->method( 'run' )
			->willReturn( [ 'Success', 'success' ] );

		$form = $this->createMock( Form::class );
		$form->expects( $this->once() )
			->method( 'setValues' );
		$form->expects( $this->once() )
			->method( 'isValid' )
			->willReturn( false );

		$this->optionsPage->expects( $this->once() )
			->method( 'createForm' )
			->willReturn( $form );
		$this->formService->expects( $this->once() )
			->method( 'formatFormErrorsToCliMessage' )
			->with( $form )
			->willReturn( 'Form validation failed' );
		$this->wpAdapter->expects( $this->once() )
			->method( 'cliError' )
			->with( 'Form validation failed' );
		$this->carrierEntityRepository
			->method( 'getAnyById' )
			->willReturn(
				new Carrier(
					'1',
					'Test Carrier',
					true,
					true,
					false,
					false,
					true,
					true,
					false,
					true,
					'cz',
					'CZK',
					10.0,
					true,
					false,
					false
				)
			);

		$command = new CarrierSettingsImportCommand(
			__DIR__ . '/config/invalid-form.php',
			$this->wpAdapter,
			$this->optionsPage,
			$this->formService,
			$this->carrierDownloader,
			$this->carrierEntityRepository
		);

		$command->__invoke();
	}
}
