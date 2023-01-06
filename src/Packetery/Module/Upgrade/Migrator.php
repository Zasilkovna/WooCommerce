<?php
/**
 * Class Migrator.
 *
 * @package Packetery\Upgrade
 */

declare( strict_types=1 );


namespace Packetery\Module\Upgrade;

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
	public function __construct( Version_1_4_2 $version_1_4_2 ) {
		$this->version_1_4_2 = $version_1_4_2;
	}


	/**
	 * Run migration.
	 *
	 * @param string $oldVersion Old version.
	 *
	 * @return void
	 */
	public function run( string $oldVersion ) {
		if ( $oldVersion && version_compare( $oldVersion, '1.4.2', '<' ) ) {
			$this->version_1_4_2->run();
		}
	}


}
