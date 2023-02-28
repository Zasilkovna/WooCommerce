<?php
/**
 * Class Migrator.
 *
 * @package Packetery\Upgrade
 */

declare( strict_types=1 );


namespace Packetery\Module\Upgrade;

use Packetery\Module\Plugin;

/**
 * Class Migrator.
 */
class Migrator {

	/**
	 * Version 1.4.2.
	 *
	 * @var Version_1_4_2
	 */
	private $version_1_4_2;

	/**
	 * Migrator constructor.
	 *
	 * @param Version_1_4_2 $version_1_4_2 Version 1.4.2.
	 */
	public function __construct( Version_1_4_2 $version_1_4_2, Container $container ) {
		$this->version_1_4_2 = $version_1_4_2;
	}

	public function run() {

		$migrations = $container->getByType( IMigration::class );
		$applicableHooks    = [];
		foreach ( $migrations as $version ) {
			if ( ! $version->isApplicable() ) {
				continue;
			}
			foreach ( $version->getHookNames() as $hookName => $callback ) {
				$migrationStatus = as_get_action_status( $hookName );
				if ( in_array( [ 'completed' ], $migrationStatus ) ) {
					continue;
				}
				$applicableHooks[ $version->getSortKey() ][ $hookName ] = (object) [    // definovat value-object třídu
					'name'     => $hookName,
					'version'  => $version,
					'callback' => $callback,
				];
			}
		}

		if ( empty( $applicableHooks ) ) {
			return;
		}

		$orderedApplicableHooks = $applicableHooks;
		krsort( $orderedApplicableHooks );

		$next = null;
		foreach ( $orderedApplicableHooks as & $hooks ) {
			rsort( $hooks );
			foreach ( $hooks as $hook ) {
				$hook->next = $next;
				$next       = $hook;
			}
		}

		ksort( $orderedApplicableHooks );

		$hook = $orderedApplicableHooks[0][0];

		$hook->callback = function () use ( $hook ) {
			$hook->callback();

			if ( $hook->next ) {
				$this->scheduleIfNotScheduled( $hook->next );
			} else {
				update_option( 'packetery_version', $hook->version->getVersion() );
			}
		};

		$this->scheduleIfNotScheduled( $hook );
	}

	public function scheduleIfNotScheduled( \stdClass $hook ) {
		if ( as_has_scheduled_action( $hook ) ) {
			return;
		}

		add_action( $hook->name, $hook ); // třída Hook bude mít metodu __invoke()
		as_schedule( $hook->name );
	}

	/**
	 * Run migration.
	 *
	 * @param string $oldVersion Old version.
	 *
	 * @return void
	 */
	public function oldrun( string $oldVersion ) {
		if ( $oldVersion && version_compare( $oldVersion, '1.4.2', '<' ) ) {
			$this->version_1_4_2->run();
		}
	}


}
