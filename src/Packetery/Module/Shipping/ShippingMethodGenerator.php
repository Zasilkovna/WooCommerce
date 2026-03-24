<?php

declare( strict_types=1 );

namespace Packetery\Module\Shipping;

use Packetery\Module\Framework\WpAdapter;
use Packetery\Nette\PhpGenerator\PhpFile;

class ShippingMethodGenerator {
	private const TARGET_NAMESPACE = 'Packetery\Module\Shipping\Generated';

	private WpAdapter $wpAdapter;

	public function __construct( WpAdapter $wpAdapter ) {
		$this->wpAdapter = $wpAdapter;
	}

	/**
	 * @link https://doc.nette.org/en/php-generator
	 */
	public function generateClass( string $carrierId, string $carrierName ): bool {
		$file = new PhpFile();
		$file->addComment( 'This file is auto-generated.' );
		$file->setStrictTypes();

		$namespace = $file->addNamespace( self::TARGET_NAMESPACE );
		$namespace->addUse( BaseShippingMethod::class );

		$className = self::getClassnameFromCarrierId( $carrierId );
		$class     = $namespace->addClass( $className );
		$class->setExtends( BaseShippingMethod::class )->addComment( $carrierName );

		// Add setType later.
		$class->addConstant( 'CARRIER_ID', $carrierId )->setPublic();

		$targetDirectory = self::getTargetDirectory();
		if ( $this->wpAdapter->isWritable( $targetDirectory ) === false ) {
			return false;
		}

		$filePath = $targetDirectory . '/' . $className . '.php';

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		return (bool) file_put_contents( $filePath, (string) $file );
	}

	public static function getTargetDirectory(): string {
		return __DIR__ . '/Generated';
	}

	private static function getClassnameFromCarrierId( string $carrierId ): string {
		return 'ShippingMethod_' . $carrierId;
	}

	public static function classExists( string $carrierId ): bool {
		return class_exists( self::TARGET_NAMESPACE . '\\' . self::getClassnameFromCarrierId( $carrierId ) );
	}
}
