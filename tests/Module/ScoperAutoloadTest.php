<?php

declare( strict_types=1 );

namespace Tests\Module;

use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ScoperAutoloadTest extends TestCase {
	private const DEPS_COMPOSER_DIR = __DIR__ . '/../../deps/composer';
	private const CLASSMAP_FILE     = 'autoload_classmap.php';
	private const STATIC_FILE       = 'autoload_static.php';
	private const PREFIX            = 'Packetery\\';

	/**
	 * @return array<string, array{string}>
	 */
	public static function classMapFileProvider(): array {
		return [
			self::CLASSMAP_FILE => [ self::CLASSMAP_FILE ],
			self::STATIC_FILE   => [ self::STATIC_FILE ],
		];
	}

	/**
	 * @dataProvider classMapFileProvider
	 */
	public function testClassMapKeysArePrefixed( string $classMapFile ): void {
		$classMap       = $this->getClassMap( $classMapFile );
		$unprefixedKeys = array_filter(
			array_keys( $classMap ),
			static function ( string $className ): bool {
				return strpos( $className, self::PREFIX ) !== 0;
			}
		);

		self::assertNotEmpty( $classMap );
		self::assertSame( [], array_values( $unprefixedKeys ) );
	}

	/**
	 * @return array<string, string>
	 */
	private function getClassMap( string $classMapFile ): array {
		if ( $classMapFile === self::STATIC_FILE ) {
			return $this->getStaticClassMap();
		}

		return require self::DEPS_COMPOSER_DIR . '/' . $classMapFile;
	}

	/**
	 * @return array<string, string>
	 */
	private function getStaticClassMap(): array {
		$staticFile = realpath( self::DEPS_COMPOSER_DIR . '/' . self::STATIC_FILE );
		foreach ( get_declared_classes() as $className ) {
			if ( strpos( $className, 'Composer\\Autoload\\ComposerStaticInit' ) !== 0 ) {
				continue;
			}

			$reflection = new ReflectionClass( $className );
			if ( $reflection->getFileName() === $staticFile ) {
				$classMap = $reflection->getStaticPropertyValue( 'classMap' );
				self::assertIsArray( $classMap );

				return $classMap;
			}
		}

		self::fail( 'The static class map of the deps autoloader is not loaded' );
	}
}
