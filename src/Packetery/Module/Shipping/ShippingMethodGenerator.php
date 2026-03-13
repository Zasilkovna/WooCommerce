<?php

declare( strict_types=1 );

namespace Packetery\Module\Shipping;

use Packetery\Nette\PhpGenerator\PhpFile;

class ShippingMethodGenerator {
	private const TARGET_NAMESPACE = 'Packetery\Module\Shipping\Generated';

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

		$filePath = __DIR__ . '/Generated/' . $className . '.php';
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
		if ( ! is_writable( $filePath ) ) {
			return false;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		return (bool) file_put_contents( $filePath, $file );
	}

	private static function getClassnameFromCarrierId( string $carrierId ): string {
		return 'ShippingMethod_' . $carrierId;
	}

	public static function classExists( string $carrierId ): bool {
		return class_exists( self::TARGET_NAMESPACE . '\\' . self::getClassnameFromCarrierId( $carrierId ) );
	}
}
