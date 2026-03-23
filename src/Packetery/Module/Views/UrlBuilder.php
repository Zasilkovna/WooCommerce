<?php

declare( strict_types=1 );

namespace Packetery\Module\Views;

use Packetery\Module\Carrier\OptionsPage;
use Packetery\Module\Carrier\ShippingClassPage;
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

	public function getCarrierConfigLink( string $carrierId, ?string $classId = null ): string {
		$parameters = [
			'page'                            => OptionsPage::SLUG,
			OptionsPage::PARAMETER_CARRIER_ID => $carrierId,
		];
		if ( $classId !== null ) {
			$parameters[ ShippingClassPage::PARAMETER_CLASS_ID ] = $classId;
		}

		return $this->wpAdapter->addQueryArg(
			$parameters,
			$this->wpAdapter->getAdminUrl( null, 'admin.php' )
		);
	}
}
