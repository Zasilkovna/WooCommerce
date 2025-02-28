<?php
/**
 * Class WpAdapter.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Framework;

use DateTimeZone;
use WP_Error;
use WP_Screen;
use WP_Term;

use function is_string;

/**
 * Class WpAdapter.
 *
 * @package Packetery
 */
class WpAdapter {
	use AssetTrait;
	use EscapingTrait;
	use HookTrait;
	use HttpTrait;
	use OptionTrait;
	use TransientTrait;
	use TranslationTrait;
	use PostTrait;

	/**
	 * Retrieves a modified URL query string.
	 *
	 * @param mixed ...$args Arguments.
	 *
	 * @return string New URL query string (unescaped).
	 */
	public function addQueryArg( ...$args ): string {
		return add_query_arg( ...$args );
	}

	/**
	 * Gets WP term.
	 *
	 * @param int $termId Term id.
	 *
	 * @return WP_Term|WP_Error|null
	 */
	public function getTerm( int $termId ) {
		return get_term( $termId );
	}

	/**
	 * Checks whether the given variable is an instance of the `WP_Error` class.
	 *
	 * @param mixed $thing Variable to check.
	 *
	 * @return bool
	 */
	public function isWpError( $thing ): bool {
		return is_wp_error( $thing );
	}

	public function getCurrentScree(): ?WP_Screen {
		return get_current_screen();
	}

	/**
	 * @return false|string
	 */
	public function date( string $format, ?int $timestamp = null, ?DateTimeZone $timezone = null ) {
		return wp_date( $format, $timestamp, $timezone );
	}

	public function adminUrl( string $path = '', string $scheme = 'admin' ): ?string {
		return admin_url( $path, $scheme );
	}

	public function doingAjax(): bool {
		return wp_doing_ajax();
	}

	public function pluginBasename( string $file ): string {
		return plugin_basename( $file );
	}

	public function isAdmin(): bool {
		return is_admin();
	}

	/**
	 * Phpdoc is not reliable.
	 *
	 * @return string|false
	 */
	public function createNonce( string $action ) {
		return wp_create_nonce( $action );
	}

	public function sendJson( array $settings ): void {
		wp_send_json( $settings );
	}

	public function isUserLoggedIn(): bool {
		return is_user_logged_in();
	}

	public function getSessionToken(): string {
		return wp_get_session_token();
	}

	/**
	 * @param mixed $data
	 *
	 * @return string
	 */
	public function jsonEncode( $data ): string {
		$encodedData = wp_json_encode( $data );

		return is_string( $encodedData ) ? $encodedData : '';
	}

	public function isMultisite(): bool {
		return is_multisite();
	}

	/**
	 * @return array<string, array<string, string|bool>>
	 */
	public function getMuPlugins(): array {
		return get_mu_plugins();
	}

	/**
	 * @return false|mixed
	 */
	public function getSiteOption( string $option ) {
		return get_site_option( $option );
	}

	/**
	 * Returns non-negative int.
	 *
	 * @param mixed $maybeInt
	 *
	 * @return int
	 */
	public function absint( $maybeInt ): int {
		return absint( $maybeInt );
	}

	public function getAdminUrl( ?int $blogId = null, string $path = '', string $scheme = 'admin' ): string {
		return get_admin_url( $blogId, $path, $scheme );
	}
}
