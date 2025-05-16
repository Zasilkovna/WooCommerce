<?php

declare(strict_types=1);

namespace Packetery\Module\Command;

use WP_CLI;

class CommandRegistrar {
	/**
	 * @var PluginInitCommand
	 */
	private $pluginInitCommand;

	/**
	 * @var CarrierSettingsImportCommand
	 */
	private $carrierBulkImportCommand;

	public function __construct(
		PluginInitCommand $pluginInitCommand,
		CarrierSettingsImportCommand $carrierBulkImportCommand
	) {
		$this->pluginInitCommand        = $pluginInitCommand;
		$this->carrierBulkImportCommand = $carrierBulkImportCommand;
	}

	public function register(): void {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( PluginInitCommand::NAME, $this->pluginInitCommand );
			WP_CLI::add_command( CarrierSettingsImportCommand::NAME, $this->carrierBulkImportCommand );
		}
	}
}
