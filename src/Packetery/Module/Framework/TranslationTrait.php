<?php

namespace Packetery\Module\Framework;

trait TranslationTrait {
	/**
	 * It is not possible to use esc_html__ or esc_attr__ this way.
	 */
	public function __( string $text, string $domain = 'default' ): ?string {
		// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText, WordPress.WP.I18n.NonSingularStringLiteralDomain
		return __( $text, $domain );
	}

	public function getLocale(): string {
		return get_locale();
	}

	public function getUserLocale(): string {
		return get_user_locale();
	}

	public function unloadTextDomain( string $domain, bool $reloadable ): void {
		unload_textdomain( $domain, $reloadable );
	}

	public function loadPluginTextDomain( string $domain ): void {
		load_plugin_textdomain( $domain );
	}

	public function loadDefaultTextDomain(): void {
		load_default_textdomain();
	}
}
