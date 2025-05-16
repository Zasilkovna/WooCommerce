<?php

declare(strict_types=1);

namespace Tests\Module\Command;

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

	protected function setUp(): void {
		$this->wpAdapter   = $this->createMock( WpAdapter::class );
		$this->optionsPage = $this->createMock( OptionsPage::class );
		$this->formService = $this->createMock( FormService::class );
	}

	public function testSuccessfulImport(): void {
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
		$this->wpAdapter->expects( $this->once() )
			->method( 'cliSuccess' )
			->with( 'Carrier settings were imported.' );

		$command = new CarrierSettingsImportCommand(
			__DIR__ . '/config/valid-config.php',
			$this->wpAdapter,
			$this->optionsPage,
			$this->formService
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
			$this->formService
		);

		$this->expectException( \RuntimeException::class );
		$command->__invoke();
	}

	public function testInvalidConfigStructure(): void {
		$this->wpAdapter->expects( $this->once() )
			->method( 'cliError' )
			->with( 'Configuration must contain carriers and global_settings' );

		$command = new CarrierSettingsImportCommand(
			__DIR__ . '/config/invalid-structure.php',
			$this->wpAdapter,
			$this->optionsPage,
			$this->formService
		);

		$command->__invoke();
	}

	public function testInvalidFormData(): void {
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

		$command = new CarrierSettingsImportCommand(
			__DIR__ . '/config/invalid-form.php',
			$this->wpAdapter,
			$this->optionsPage,
			$this->formService
		);

		$command->__invoke();
	}
}
