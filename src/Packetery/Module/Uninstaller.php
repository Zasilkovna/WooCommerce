<?php

declare(strict_types=1);

namespace Packetery\Module;

use Packetery\Module\Options\OptionsProvider;

class Uninstaller {

	/**
	 * @var Options\Repository
	 */
	private $optionsRepository;

	/**
	 * @var WpdbAdapter
	 */
	private $wpdbAdapter;

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
	 */
	private function cleanUp(): void {
		$this->wpdbAdapter->query( 'DROP TABLE IF EXISTS `' . $this->wpdbAdapter->packeteryLog . '`' );
		$this->wpdbAdapter->query( 'DROP TABLE IF EXISTS `' . $this->wpdbAdapter->packeteryCarrier . '`' );
		$this->wpdbAdapter->query( 'DROP TABLE IF EXISTS `' . $this->wpdbAdapter->packeteryOrder . '`' );
		$this->wpdbAdapter->query( 'DROP TABLE IF EXISTS `' . $this->wpdbAdapter->packeteryCustomsDeclarationItem . '`' );
		$this->wpdbAdapter->query( 'DROP TABLE IF EXISTS `' . $this->wpdbAdapter->packeteryCustomsDeclaration . '`' );

		$transientsToDelete = $this->optionsRepository->getAllTransientsByPrefixes(
			[
				'packetery_',
				'packeta_',
			]
		);

		foreach ( $transientsToDelete as $optionName ) {
			delete_transient( $optionName );
		}

		$optionNamesToDelete = $this->optionsRepository->getAllOptionNamesByPrefixes(
			[
				'packetery_',
				'packeta_',
			]
		);

		foreach ( $optionNamesToDelete as $optionName ) {
			delete_option( $optionName );
		}

		delete_option( OptionsProvider::OPTION_NAME_PACKETERY );
	}
}
