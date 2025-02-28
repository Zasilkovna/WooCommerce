<?php

declare( strict_types=1 );

namespace Packetery\Module\Views;

use Packetery\Module\Framework\WpAdapter;

class UrlBuilder {
	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	public function __construct( WpAdapter $wpAdapter ) {
		$this->wpAdapter = $wpAdapter;
	}

	/**
	 * Builds asset URL.
	 *
	 * @param string $asset Relative asset path without leading slash.
	 *
	 * @return string|null
	 */
	public function buildAssetUrl( string $asset ): ?string {
		$url      = $this->wpAdapter->pluginDirUrl( PACKETERY_PLUGIN_DIR . '/packeta.php' ) . $asset;
		$filename = PACKETERY_PLUGIN_DIR . '/' . $asset;

		if ( ! file_exists( $filename ) ) {
			return null;
		}

		return $this->wpAdapter->addQueryArg( [ 'v' => md5( (string) filemtime( $filename ) ) ], $url );
	}
}
