<?php

declare(strict_types=1);

namespace Packetery\Module\Command;

use Packetery\Module\Carrier\EntityRepository;
use Packetery\Module\Commands\DemoOrderCommand;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Order\Builder;
use Packetery\Module\Order\Repository;
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
	 * @var Builder
	 */
	private $builder;

	/**
	 * @var Repository
	 */
	private $orderRepository;

	/**
	 * @var EntityRepository
	 */
	private $carrierRepository;

	/**
	 * @var OptionsProvider
	 */
	private $optionsProvider;

	public function __construct(
		PluginInitCommand $pluginInitCommand,
		CarrierSettingsImportCommand $carrierBulkImportCommand,
		Builder $builder,
		Repository $orderRepository,
		EntityRepository $carrierRepository,
		OptionsProvider $optionsProvider
	) {
		$this->pluginInitCommand        = $pluginInitCommand;
		$this->carrierBulkImportCommand = $carrierBulkImportCommand;
		$this->builder           = $builder;
		$this->orderRepository   = $orderRepository;
		$this->carrierRepository = $carrierRepository;
		$this->optionsProvider   = $optionsProvider;
	}

	public function register(): void {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( PluginInitCommand::NAME, $this->pluginInitCommand );
			WP_CLI::add_command( CarrierSettingsImportCommand::NAME, $this->carrierBulkImportCommand );

			$demoOrderCommand = DemoOrderCommand::createCommand( $this->builder, $this->orderRepository, $this->carrierRepository, $this->optionsProvider );
			WP_CLI::add_command( 'packeta-plugin-build-demo-order', $demoOrderCommand );
		}
	}
}
