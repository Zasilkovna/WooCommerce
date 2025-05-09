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

	/**
	 * @var DemoOrderCommand
	 */
	private $demoOrderCommand;

	public function __construct(
		PluginInitCommand $pluginInitCommand,
		CarrierSettingsImportCommand $carrierBulkImportCommand,
		DemoOrderCommand $demoOrderCommand
	) {
		$this->pluginInitCommand        = $pluginInitCommand;
		$this->carrierBulkImportCommand = $carrierBulkImportCommand;
		$this->demoOrderCommand         = $demoOrderCommand;
	}

	public function register(): void {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( PluginInitCommand::NAME, $this->pluginInitCommand );
			WP_CLI::add_command( CarrierSettingsImportCommand::NAME, $this->carrierBulkImportCommand );
			WP_CLI::add_command( DemoOrderCommand::NAME, $this->demoOrderCommand );
		}
	}
}
