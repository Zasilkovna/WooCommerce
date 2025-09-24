<?php

declare( strict_types=1 );

namespace Tests\Integration\Module\Views;

use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Views\UrlBuilder;
use Tests\Integration\AbstractIntegrationTestCase;

class UrlBuilderTest extends AbstractIntegrationTestCase {
	private function createUrlBuilder(): UrlBuilder {
		/** @var WpAdapter $wpAdapter */
		$wpAdapter = $this->container->getByType( WpAdapter::class );

		return new UrlBuilder( $wpAdapter );
	}

	public function testBuildAssetUrlReturnsNullForMissingAsset(): void {
		$urlBuilder = $this->createUrlBuilder();

		self::assertNull( $urlBuilder->buildAssetUrl( 'temp/does-not-exist-' . uniqid( '', true ) . '.txt' ) );
	}

	public function testBuildAssetUrlReturnsVersionedUrlForExistingFile(): void {
		$urlBuilder = $this->createUrlBuilder();

		$directoryPath = PACKETERY_PLUGIN_DIR . '/temp/tests';
		$filePath      = $directoryPath . '/urlbuilder-' . uniqid( '', true ) . '.txt';
		$relativeAsset = 'temp/tests/' . basename( $filePath );

		if ( ! is_dir( $directoryPath ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
			mkdir( $directoryPath, 0777, true );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $filePath, 'test' );
		clearstatcache( true, $filePath );

		$url = $urlBuilder->buildAssetUrl( $relativeAsset );

		self::assertNotNull( $url );
		self::assertStringEndsWith( $relativeAsset, (string) preg_replace( '/\?.*/', '', (string) $url ) );

		$modificationTime = filemtime( $filePath );
		$version          = md5( (string) $modificationTime );

		$query = (string) wp_parse_url( (string) $url, PHP_URL_QUERY );
		parse_str( $query, $params );
		self::assertArrayHasKey( 'v', $params );
		self::assertSame( $version, $params['v'] );

		wp_delete_file( $filePath );
	}
}
