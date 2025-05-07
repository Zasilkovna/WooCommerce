<?php

declare(strict_types=1);

namespace Packetery\Module\Command;

use WP_CLI;

class CommandRegistrar {
	/**
	 * @var PluginInitCommand
	 */
	private $pluginInitCommand;

	public function __construct(
		PluginInitCommand $pluginInitCommand
	) {
		$this->pluginInitCommand = $pluginInitCommand;
	}

	public function register(): void {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'packeta-plugin-init', $this->pluginInitCommand );
		}
	}
}
