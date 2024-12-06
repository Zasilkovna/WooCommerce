<?php
/**
 * Uninstaller class.
 *
 * @package Packetery
 */

declare(strict_types=1);

namespace Packetery\Module;

use Packetery\Module\Options\OptionsProvider;

/**
 * Uninstaller class.
 *
 * @package Packetery
 */
class Uninstaller {
	/**
	 * Options repository.
	 *
	 * @var Options\Repository
	 */
	private $optionsRepository;

	/**
	 * Wpdb adapter.
	 *
	 * @var WpdbAdapter
	 */
	private $wpdbAdapter;

	/**
	 * Constructor.
	 *
	 * @param Options\Repository $optionsRepository Options repository.
	 * @param WpdbAdapter        $wpdbAdapter       Wpdb adapter.
	 */
	public function __construct(
		Options\Repository $optionsRepository,
		WpdbAdapter $wpdbAdapter
	) {
		$this->optionsRepository = $optionsRepository;
		$this->wpdbAdapter       = $wpdbAdapter;
	}

	/**
	 * Uninstalls plugin and drops custom database table.
	 */
	public function uninstall(): void {
		if ( defined( 'PACKETERY_DEBUG' ) && constant( 'PACKETERY_DEBUG' ) === true ) {
			return;
		}

		if ( is_multisite() ) {
			$this->cleanUpForMultisite();
		} else {
			$this->cleanUp();
		}
	}

	/**
	 * Drops all plugin tables when Multisite is enabled.
	 *
	 * @return void
	 */
	private function cleanUpForMultisite(): void {
		$sites = Plugin::getSites();

		foreach ( $sites as $site ) {
			switch_to_blog( $site );
			$this->cleanUp();
			restore_current_blog();
		}
	}

	/**
	 * Drops all plugin tables for a single site.
	 *
	 * @return void
	 */
	private function cleanUp(): void {
		$this->wpdbAdapter->query( 'DROP TABLE IF EXISTS `' . $this->wpdbAdapter->packeteryLog . '`' );
		$this->wpdbAdapter->query( 'DROP TABLE IF EXISTS `' . $this->wpdbAdapter->packeteryCarrier . '`' );
		$this->wpdbAdapter->query( 'DROP TABLE IF EXISTS `' . $this->wpdbAdapter->packeteryOrder . '`' );
		$this->wpdbAdapter->query( 'DROP TABLE IF EXISTS `' . $this->wpdbAdapter->packeteryCustomsDeclarationItem . '`' );
		$this->wpdbAdapter->query( 'DROP TABLE IF EXISTS `' . $this->wpdbAdapter->packeteryCustomsDeclaration . '`' );

		$this->optionsRepository->deleteTransientsByPrefix( 'packetery_' );
		$this->optionsRepository->deleteTransientsByPrefix( 'packeta_' );

		$this->optionsRepository->deleteByPrefix( 'packetery_' );
		$this->optionsRepository->deleteByPrefix( 'packeta_' );

		delete_option( OptionsProvider::OPTION_NAME_PACKETERY );
	}
}
