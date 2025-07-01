<?php
/**
 * Main Packeta plugin class.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module;

use Packetery\Module\Hooks\HookRegistrar;

/**
 * Class Plugin
 *
 * @package Packetery
 */
class Plugin {

	public const VERSION                = '2.0.7';
	public const DOMAIN                 = 'packeta';
	public const PARAM_PACKETERY_ACTION = 'packetery_action';
	public const PARAM_NONCE            = '_wpnonce';

	/**
	 * @var HookRegistrar
	 */
	private $hookRegistrar;

	public function __construct( HookRegistrar $hookRegistrar ) {
		$this->hookRegistrar = $hookRegistrar;
	}

	/**
	 * Gets list of multisite sites.
	 *
	 * @return array
	 */
	public static function getSites(): array {
		return get_sites(
			[
				'fields'            => 'ids',
				'number'            => 0,
				'update_site_cache' => false,
			]
		);
	}

	/**
	 * Method to register hooks
	 */
	public function run(): void {
		$this->hookRegistrar->register();
	}

	/**
	 * Uninstalls plugin and drops custom database table.
	 * Only a static class method or function can be used in an uninstall hook.
	 */
	public static function uninstall(): void {
		if ( defined( 'PACKETERY_DEBUG' ) && constant( 'PACKETERY_DEBUG' ) === true ) {
			return;
		}

		if ( is_multisite() ) {
			self::cleanUpRepositoriesForMultisite();
		} else {
			self::cleanUpRepositories();
		}
	}

	/**
	 * Drops all plugin tables when Multisite is enabled.
	 *
	 * @return void
	 */
	private static function cleanUpRepositoriesForMultisite(): void {
		$sites = self::getSites();

		foreach ( $sites as $site ) {
			switch_to_blog( $site );
			self::cleanUpRepositories();
			restore_current_blog();
		}
	}

	/**
	 * Drops all plugin tables for a single site.
	 *
	 * @return void
	 */
	private static function cleanUpRepositories(): void {
		$container = require PACKETERY_PLUGIN_DIR . '/bootstrap.php';

		$optionsRepository = $container->getByType( Options\Repository::class );
		$pluginOptions     = $optionsRepository->getPluginOptions();
		foreach ( $pluginOptions as $option ) {
			// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
			delete_option( $option->option_name );
		}

		$logRepository = $container->getByType( Log\Repository::class );
		$logRepository->drop();

		$carrierRepository = $container->getByType( Carrier\Repository::class );
		$carrierRepository->drop();

		$orderRepository = $container->getByType( Order\Repository::class );
		$orderRepository->drop();

		$customsDeclarationItemsRepository = $container->getByType( CustomsDeclaration\Repository::class );
		$customsDeclarationItemsRepository->dropItems();

		$customsDeclarationRepository = $container->getByType( CustomsDeclaration\Repository::class );
		$customsDeclarationRepository->drop();
	}

	/**
	 * Hides submenu item. Must not be called too early.
	 *
	 * @param string $itemSlug Item slug.
	 */
	public static function hideSubmenuItem( string $itemSlug ): void {
		global $submenu;
		if ( isset( $submenu[ Options\Page::SLUG ] ) ) {
			foreach ( $submenu[ Options\Page::SLUG ] as $key => $menu ) {
				if ( $itemSlug === $menu[2] ) {
					unset( $submenu[ Options\Page::SLUG ][ $key ] );
				}
			}
		}
	}

	/**
	 * Gets software identity for Packeta APIs.
	 *
	 * @return string
	 */
	public static function getAppIdentity(): string {
		return 'WordPress-' . get_bloginfo( 'version' ) . '-Woocommerce-' . WC_VERSION . '-Packeta-' . self::VERSION;
	}
}
