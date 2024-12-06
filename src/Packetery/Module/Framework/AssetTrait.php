<?php

declare( strict_types=1 );

namespace Packetery\Module\Framework;

trait AssetTrait {
	public function pluginDirUrl( string $file ): string {
		return plugin_dir_url( $file );
	}

	/**
	 * @param string       $handle
	 * @param string       $src
	 * @param array        $deps
	 * @param string|false $ver
	 * @param array|bool   $args
	 */
	public function enqueueScript( string $handle, string $src = '', array $deps = [], $ver = false, $args = [] ): void {
		// phpcs:ignore Squiz.Commenting.FunctionComment.ParamNameNoMatch
		wp_enqueue_script( $handle, $src, $deps, $ver, $args );
	}

	public function localizeScript( string $handle, string $objectName, array $l10n ): void {
		wp_localize_script( $handle, $objectName, $l10n );
	}

	/**
	 * @param string       $handle
	 * @param string       $src
	 * @param array        $deps
	 * @param string|false $ver
	 * @param string       $media
	 */
	public function enqueueStyle( string $handle, string $src = '', array $deps = [], $ver = false, string $media = 'all' ): void {
		wp_enqueue_style( $handle, $src, $deps, $ver, $media );
	}
}
