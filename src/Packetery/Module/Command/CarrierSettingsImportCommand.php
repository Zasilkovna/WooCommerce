<?php

declare(strict_types=1);

namespace Packetery\Module\Command;

use InvalidArgumentException;
use Packetery\Module\Carrier\Downloader;
use Packetery\Module\Carrier\EntityRepository;
use Packetery\Module\Carrier\OptionsPage;
use Packetery\Module\Forms\FormService;
use Packetery\Module\Framework\WpAdapter;

class CarrierSettingsImportCommand {
	const NAME = 'packeta-carrier-settings-import';

	/**
	 * @var string
	 */
	private $configPath;

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * @var OptionsPage
	 */
	private $optionsPage;

	/**
	 * @var FormService
	 */
	private $formService;

	/**
	 * @var Downloader
	 */
	private $carrierDownloader;

	/**
	 * @var EntityRepository
	 */
	private $carrierEntityRepository;

	/**
	 * @param string $configPath
	 */
	public function __construct(
		$configPath,
		WpAdapter $wpAdapter,
		OptionsPage $optionsPage,
		FormService $formService,
		Downloader $carrierDownloader,
		EntityRepository $carrierEntityRepository
	) {
		$this->configPath              = $configPath;
		$this->wpAdapter               = $wpAdapter;
		$this->optionsPage             = $optionsPage;
		$this->formService             = $formService;
		$this->carrierDownloader       = $carrierDownloader;
		$this->carrierEntityRepository = $carrierEntityRepository;
	}

	/**
	 * Imports carrier configuration from a PHP file.
	 *
	 * ## EXAMPLES
	 *
	 *     wp packeta-carrier-settings-import
	 */
	public function __invoke(): void {
		if ( ! is_file( $this->configPath ) ) {
			$this->wpAdapter->cliError( 'Config file does not exist.' );

			return;
		}

		$config = require $this->configPath;
		if ( ! is_array( $config ) ) {
			$this->wpAdapter->cliError( 'Config file must return an array.' );

			return;
		}

		[ $message, $result ] = $this->carrierDownloader->run();
		if ( $result !== 'success' ) {
			$this->wpAdapter->cliError( $message );

			return;
		}

		try {
			$this->processConfig( $config );
			$this->wpAdapter->cliSuccess( 'Carrier settings were imported.' );
		} catch ( InvalidArgumentException $invalidArgumentException ) {
			$this->wpAdapter->cliError( $invalidArgumentException->getMessage() );
		}
	}

	/**
	 * @throws InvalidArgumentException When invalid config is provided.
	 */
	private function processConfig( array $config ): void {
		if ( ! isset( $config['carriers'] ) || ! isset( $config['global_settings'] ) ) {
			throw new InvalidArgumentException( 'Configuration must contain carriers and global_settings' );
		}

		$globalSettings = $config['global_settings'];
		$carriers       = $config['carriers'];

		foreach ( $carriers as $carrierId => $carrierConfig ) {
			if ( $this->carrierEntityRepository->getAnyById( (string) $carrierId ) === null ) {
				$this->wpAdapter->cliLog( "Carrier with ID {$carrierId} from config does not exist in database and will be skipped." );

				continue;
			}

			$mergedConfig = array_merge(
				$globalSettings,
				[ 'id' => (string) $carrierId ],
				$carrierConfig
			);

			$this->processCarrierConfig( $mergedConfig );
		}
	}

	/**
	 * @throws InvalidArgumentException When form is invalid.
	 */
	private function processCarrierConfig( array $config ): void {
		$form = $this->optionsPage->createForm( $config );
		$form->setValues( $config );

		if ( ! $form->isValid() ) {
			throw new InvalidArgumentException( $this->formService->formatFormErrorsToCliMessage( $form ) );
		}

		$this->optionsPage->updateOptionsWithArray( $form->getValues( 'array' ) );
	}
}
