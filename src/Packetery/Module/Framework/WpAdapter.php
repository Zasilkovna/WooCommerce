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

	public function timezone(): DateTimeZone {
		return wp_timezone();
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

	public function switchToBlog( int $site ): bool {
		return switch_to_blog( $site );
	}

	public function restoreCurrentBlog(): bool {
		return restore_current_blog();
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

	public function addSubmenuPage(
		string $parentSlug,
		string $pageTitle,
		string $menuTitle,
		string $capability,
		string $menuSlug,
		callable $callback,
		?int $position = null
	): void {
		add_submenu_page(
			$parentSlug,
			$pageTitle,
			$menuTitle,
			$capability,
			$menuSlug,
			$callback,
			$position
		);
	}

	public function addMenuPage(
		string $pageTitle,
		string $menuTitle,
		string $capability,
		string $menuSlug,
		callable $callback,
		string $iconUrl
	): void {
		add_menu_page(
			$pageTitle,
			$menuTitle,
			$capability,
			$menuSlug,
			$callback,
			$iconUrl
		);
	}

	public function addShortcode( string $tag, callable $callback ): void {
		add_shortcode( $tag, $callback );
	}

	public function doShortcode( string $content ): string {
		return do_shortcode( $content );
	}

	public function sanitizeEmail( string $email ): string {
		return sanitize_email( $email );
	}

	public function wpKsesPost( string $content ): string {
		return wp_kses_post( $content );
	}

	public function wpStripAllTags( string $content ): string {
		return wp_strip_all_tags( $content );
	}

	public function enqueueEditor(): void {
		wp_enqueue_editor();
	}

	public function getBlogInfo( string $show, string $filter ): string {
		return get_bloginfo( $show, $filter );
	}

	/**
	 * @param string|string[] $to
	 * @param string          $subject
	 * @param string          $message
	 * @param string[]        $headers
	 * @param string[]        $attachments
	 */
	public function wpMail( $to, string $subject, string $message, array $headers, array $attachments ): bool {
		return wp_mail( $to, $subject, $message, $headers, $attachments );
	}

	public function currentTime( string $type, bool $gmt ): string {
		return (string) current_time( $type, $gmt );
	}
}
