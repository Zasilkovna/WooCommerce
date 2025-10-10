<?php

declare(strict_types=1);

namespace Packetery\Module;

use Packetery\Module\Carrier\OptionPrefixer;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionNames;

class Uninstaller {

	/**
	 * @var Options\Repository
	 */
	private $optionsRepository;

	/**
	 * @var WpdbAdapter
	 */
	private $wpdbAdapter;

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	public function __construct(
		Options\Repository $optionsRepository,
		WpdbAdapter $wpdbAdapter,
		WpAdapter $wpAdapter
	) {
		$this->optionsRepository = $optionsRepository;
		$this->wpdbAdapter       = $wpdbAdapter;
		$this->wpAdapter         = $wpAdapter;
	}

	/**
	 * Uninstalls plugin and drops custom database table.
	 */
	public function uninstall(): void {
		if ( defined( 'PACKETERY_DEBUG' ) && constant( 'PACKETERY_DEBUG' ) === true ) {
			return;
		}
		if ( defined( 'PACKETERY_REMOVE_ALL_DATA' ) && constant( 'PACKETERY_REMOVE_ALL_DATA' ) === true ) {
			if ( $this->wpAdapter->isMultisite() ) {
				$this->cleanUpForMultisite();
			} else {
				$this->cleanUp();
			}
		}
	}

	/**
	 * Drops all plugin tables, options and transients when Multisite is enabled.
	 */
	private function cleanUpForMultisite(): void {
		$sites = Plugin::getSites();

		foreach ( $sites as $site ) {
			$this->wpAdapter->switchToBlog( $site );
			$this->cleanUp();
			$this->wpAdapter->restoreCurrentBlog();
		}
	}

	/**
	 * Drops all plugin tables, options and transients for a single site.
	 */
	private function cleanUp(): void {
		$this->wpdbAdapter->query( 'DROP TABLE IF EXISTS `' . $this->wpdbAdapter->packeteryLog . '`' );
		$this->wpdbAdapter->query( 'DROP TABLE IF EXISTS `' . $this->wpdbAdapter->packeteryCarrier . '`' );
		$this->wpdbAdapter->query( 'DROP TABLE IF EXISTS `' . $this->wpdbAdapter->packeteryOrder . '`' );
		$this->wpdbAdapter->query( 'DROP TABLE IF EXISTS `' . $this->wpdbAdapter->packeteryCustomsDeclarationItem . '`' );
		$this->wpdbAdapter->query( 'DROP TABLE IF EXISTS `' . $this->wpdbAdapter->packeteryCustomsDeclaration . '`' );

		$transientsToDelete = $this->optionsRepository->getAllTransientsByPrefixes(
			[
				Transients::CHECKOUT_DATA_PREFIX,
				Transients::MESSAGE_MANAGER_MESSAGES_PREFIX,
				Transients::ORDER_COLLECTION_PRINT_ORDER_IDS_PREFIX,
				Transients::LABEL_PRINT_ORDER_IDS_PREFIX,
				Transients::LABEL_PRINT_BACK_LINK_PREFIX,
			]
		);

		foreach ( $transientsToDelete as $optionName ) {
			$this->wpAdapter->deleteTransient( $optionName );
		}

		$this->wpAdapter->deleteTransient( Transients::METABOX_NETTE_FORM_PREV_INVALID_VALUES );
		$this->wpAdapter->deleteTransient( Transients::RUN_UPDATE_CARRIERS );
		$this->wpAdapter->deleteTransient( Transients::CARRIER_CHANGES );
		$this->wpAdapter->deleteTransient( Transients::SPLIT_MESSAGE_DISMISSED );

		$optionNamesToDelete = $this->optionsRepository->getAllOptionNamesByPrefixes(
			[
				OptionPrefixer::CARRIER_OPTION_PREFIX,
				'woocommerce_packeta_method_',
			]
		);

		foreach ( $optionNamesToDelete as $optionName ) {
			$this->wpAdapter->deleteOption( $optionName );
		}

		$this->wpAdapter->deleteOption( OptionNames::VERSION );
		$this->wpAdapter->deleteOption( OptionNames::LAST_SETTINGS_EXPORT );
		$this->wpAdapter->deleteOption( OptionNames::LAST_CARRIER_UPDATE );
		$this->wpAdapter->deleteOption( OptionNames::PACKETERY );
		$this->wpAdapter->deleteOption( OptionNames::PACKETERY_SYNC );
		$this->wpAdapter->deleteOption( OptionNames::PACKETERY_AUTO_SUBMISSION );
		$this->wpAdapter->deleteOption( OptionNames::PACKETERY_ADVANCED );
		$this->wpAdapter->deleteOption( OptionNames::PACKETERY_ACTIVATED );
		$this->wpAdapter->deleteOption( OptionNames::PACKETERY_TUTORIAL_ORDER_DETAIL_EDIT_PACKET );
		$this->wpAdapter->deleteOption( OptionNames::PACKETERY_TUTORIAL_ORDER_GRID_EDIT_PACKET );
		$this->wpAdapter->deleteOption( OptionNames::FEATURE_FLAGS );
		$this->wpAdapter->deleteOption( OptionNames::FEATURE_FLAGS_ERROR_COUNTER );
		$this->wpAdapter->deleteOption( OptionNames::FEATURE_FLAGS_DISABLED_DUE_ERRORS );

		$this->wpdbAdapter->query(
			$this->wpdbAdapter->prepare(
				"DELETE FROM `{$this->wpdbAdapter->postmeta}`
				WHERE `meta_key` IN (%s, %s)",
				Product\Entity::META_AGE_VERIFICATION_18_PLUS,
				Product\Entity::META_DISALLOWED_SHIPPING_RATES
			)
		);
	}
}
